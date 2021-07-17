package rs.miki208.myrunningbuddy;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONException;
import org.json.JSONObject;

import java.net.HttpURLConnection;
import java.util.HashMap;

import rs.miki208.myrunningbuddy.helpers.APIObjectCacheSingleton;
import rs.miki208.myrunningbuddy.helpers.APIObjectLoader;
import rs.miki208.myrunningbuddy.helpers.APIWrapper;
import rs.miki208.myrunningbuddy.helpers.AbstractAPIResponseHandler;
import rs.miki208.myrunningbuddy.helpers.ActivityHelper;

public class MatchingActivity extends AppCompatActivity {
    String suggestedRunnerId = null;

    HashMap<String, TextView> profileDataWidgets;

    Button acceptButton;
    Button rejectButton;
    LinearLayout dialogOverlay;
    TextView tvDialog;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_matching);

        profileDataWidgets = new HashMap<>();
        ActivityHelper.FillProfileDataWidgets(this, profileDataWidgets);

        dialogOverlay = findViewById(R.id.dialogOverlay);
        tvDialog = findViewById(R.id.tvDialog);

        acceptButton = findViewById(R.id.acceptButton);
        rejectButton = findViewById(R.id.rejectButton);

        View.OnClickListener onMatchActionListener = new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                boolean accepted = false;

                if(v.getId() == acceptButton.getId())
                    accepted = true;
                else if(v.getId() == rejectButton.getId())
                    accepted = false;

                if(suggestedRunnerId != null)
                    APIWrapper.PostMatchAction(getApplicationContext(), suggestedRunnerId, accepted, new AbstractAPIResponseHandler() {
                        @Override
                        public void Handle(JSONObject response, int statusCode) throws JSONException {
                            switch (statusCode)
                            {
                                case HttpURLConnection.HTTP_OK:
                                    if(!response.has("status") || response.isNull("status"))
                                        return;

                                    String status = response.getString("status");
                                    if(status.equals("accepted"))
                                    {
                                        ShowNextMatch();
                                    }
                                    else if(status.equals("rejected"))
                                    {
                                        ShowNextMatch();
                                    }
                                    else if(status.equals("matched"))
                                    {
                                        Toast.makeText(getApplicationContext(), status, Toast.LENGTH_LONG).show();
                                        // redirect to messages
                                    }
                                    break;
                                case HttpURLConnection.HTTP_UNAUTHORIZED:
                                    Intent intent = new Intent(MatchingActivity.this, LoginActivity.class);
                                    MatchingActivity.this.startActivity(intent);

                                    MatchingActivity.this.finish();
                                    break;
                                default:
                                    Toast.makeText(getApplicationContext(), APIWrapper.GetErrorMessageFromResponse(getApplicationContext(), response, statusCode), Toast.LENGTH_LONG).show();
                                    break;
                            }
                        }
                    });
            }
        };

        acceptButton.setOnClickListener(onMatchActionListener);
        rejectButton.setOnClickListener(onMatchActionListener);
    }

    @Override
    protected void onResume() {
        super.onResume();

        dialogOverlay.setVisibility(View.GONE);

        ShowNextMatch();
    }

    private void ShowNextMatch()
    {
        suggestedRunnerId = null;
        acceptButton.setEnabled(false);
        rejectButton.setEnabled(false);

        APIObjectLoader.LoadData(getApplicationContext(), "nextMatch", "me", false, 0, new APIObjectLoader.PaginationInfo(), new APIObjectLoader.APIObjectListener() {
            @Override
            public void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, APIObjectLoader.ErrorType errorCode) {
                if(errorCode == APIObjectLoader.ErrorType.AUTHORIZATION_FAILED)
                {
                    Intent intent = new Intent(MatchingActivity.this, LoginActivity.class);
                    MatchingActivity.this.startActivity(intent);

                    MatchingActivity.this.finish();

                    return;
                }

                if(errorCode != APIObjectLoader.ErrorType.NO_ERROR || obj == null || obj.objectType != APIObjectCacheSingleton.EntryType.JSONOBJECT)
                {
                    if(errorCode == APIObjectLoader.ErrorType.PRECONDITION_FAILED)
                    {
                        // user hasn't linked to external account
                        ActivityHelper.RenderClearProfile(MatchingActivity.this, profileDataWidgets);

                        tvDialog.setText(R.string.to_start_matching_connect_external_account);
                        dialogOverlay.setVisibility(View.VISIBLE);

                        return;
                    }

                    if(errorCode == APIObjectLoader.ErrorType.NOT_FOUND)
                    {
                        // there are no matches
                        ActivityHelper.RenderClearProfile(MatchingActivity.this, profileDataWidgets);

                        tvDialog.setText(R.string.no_matches_available);
                        dialogOverlay.setVisibility(View.VISIBLE);

                        return;
                    }

                    Intent intent = new Intent(MatchingActivity.this, ProfileActivity.class);
                    intent.putExtra("user_id", "me");

                    startActivity(intent);

                    return;
                }

                JSONObject potentialMatch = (JSONObject) obj.cachedObject;
                JSONObject user = ConvertToUserObject(potentialMatch);

                ActivityHelper.RenderProfile(MatchingActivity.this, profileDataWidgets, user);

                if(potentialMatch.has("suggested_runner") && !potentialMatch.isNull("suggested_runner"))
                {
                    try {
                        suggestedRunnerId = String.valueOf(potentialMatch.getJSONObject("suggested_runner").getInt("runner_id"));
                    } catch (JSONException ignored) {

                    }
                }

                if(suggestedRunnerId == null)
                    return;

                acceptButton.setEnabled(true);
                rejectButton.setEnabled(true);
            }
        });
    }

    private JSONObject ConvertToUserObject(JSONObject potentialMatch)
    {
        JSONObject result = new JSONObject();

        try {
            if(!potentialMatch.has("suggested_runner") || potentialMatch.isNull("suggested_runner"))
                return result;

            JSONObject suggestedRunner = potentialMatch.getJSONObject("suggested_runner");

            if(!suggestedRunner.has("info") || suggestedRunner.isNull("info"))
                return result;

            JSONObject runnerInfo = suggestedRunner.getJSONObject("info");

            String [] runnerInfoFields = new String[]{ "name", "surname", "aboutme", "location" };
            for(String field : runnerInfoFields) {
                if (runnerInfo.has(field) && !runnerInfo.isNull(field))
                    result.put(field, runnerInfo.getString(field));
            }

            if(!suggestedRunner.has("stats") || suggestedRunner.isNull("stats"))
                return result;

            JSONObject stats = new JSONObject();
            stats.put("stats", suggestedRunner.getJSONObject("stats"));
            result.put("stats", stats);
        } catch (Exception ignored)
        {

        }

        return result;
    }

    @Override
    public void onSaveInstanceState(Bundle savedInstanceState) {
        super.onSaveInstanceState(savedInstanceState);
    }
}