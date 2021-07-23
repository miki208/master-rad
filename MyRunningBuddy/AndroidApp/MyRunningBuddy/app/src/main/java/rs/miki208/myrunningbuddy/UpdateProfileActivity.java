package rs.miki208.myrunningbuddy;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.view.GravityCompat;
import androidx.drawerlayout.widget.DrawerLayout;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.net.HttpURLConnection;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.List;

import rs.miki208.myrunningbuddy.helpers.APIObjectCacheSingleton;
import rs.miki208.myrunningbuddy.helpers.APIObjectLoader;
import rs.miki208.myrunningbuddy.helpers.APIWrapper;
import rs.miki208.myrunningbuddy.helpers.AbstractAPIResponseHandler;
import rs.miki208.myrunningbuddy.helpers.ActivityHelper;
import rs.miki208.myrunningbuddy.helpers.GlobalVars;
import rs.miki208.myrunningbuddy.helpers.SharedPrefSingleton;

public class UpdateProfileActivity extends AppCompatActivity {
    private HashMap<String, Boolean> linkedServices = new HashMap<>();

    TextView tvStravaStatus;
    EditText etSurname;
    EditText etLocation;
    EditText etAboutMe;
    Spinner priorityField;

    private List<String> priorityFieldsSelection;
    private List<String> priorityFieldsNames;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_update_profile);

        tvStravaStatus = findViewById(R.id.tvStravaStatus);
        etSurname = findViewById(R.id.etSurname);
        etLocation = findViewById(R.id.etLocation);
        etAboutMe = findViewById(R.id.etAboutMe);
        priorityField = findViewById(R.id.priorityField);

        priorityFieldsSelection = new ArrayList<>(Arrays.asList(
                getString(R.string.none),
                getString(R.string.avg_total_distance_per_week),
                getString(R.string.avg_moving_time_per_week),
                getString(R.string.avg_longest_distance_per_week),
                getString(R.string.avg_pace_per_week),
                getString(R.string.avg_total_elevation_per_week),
                getString(R.string.avg_start_time_per_week)
        ));

        priorityFieldsNames = new ArrayList<>(Arrays.asList(
                "none",
                "avg_total_distance_per_week",
                "avg_moving_time_per_week",
                "avg_longest_distance_per_week",
                "avg_pace_per_week",
                "avg_total_elevation_per_week",
                "avg_start_time_per_week"
        ));

        ArrayAdapter<String> adapter = new ArrayAdapter<String>(this, R.layout.spinner_item, priorityFieldsSelection);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        priorityField.setAdapter(adapter);

        ActivityHelper.InitializeToolbarAndMenu(this);
    }

    @Override
    public void onBackPressed() {
        DrawerLayout drawer = (DrawerLayout) findViewById(R.id.drawer_layout);
        if(drawer.isDrawerOpen(GravityCompat.START))
            drawer.closeDrawer(GravityCompat.START);
        else
            super.onBackPressed();
    }

    @Override
    protected void onResume() {
        super.onResume();

        // set priority field
        String priorityF = (String) SharedPrefSingleton.getInstance(getApplicationContext()).GetValue("string", "priority_field");
        int selectIndex = -1;
        if(priorityF != null)
        {
            selectIndex = priorityFieldsNames.indexOf(priorityF);
        }

        if(selectIndex == -1)
            priorityField.setSelection(0);
        else
            priorityField.setSelection(selectIndex);

        // get fresh user representation
        APIObjectLoader.LoadData(getApplicationContext(), "user", "me", false, 10 * 60, null, new APIObjectLoader.APIObjectListener() {
            @Override
            public void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, APIObjectLoader.ErrorType errorCode) {
                if(errorCode == APIObjectLoader.ErrorType.AUTHORIZATION_FAILED)
                {
                    ActivityHelper.Logout(getApplicationContext(),UpdateProfileActivity.this);

                    return;
                }

                if(obj == null)
                    return;

                if(obj.objectType != APIObjectCacheSingleton.EntryType.JSONOBJECT)
                    return;

                JSONObject user = (JSONObject) obj.cachedObject;

                try
                {
                    // fill form if from representation
                    if(user.has("surname") && !user.isNull("surname"))
                        etSurname.setText(user.getString("surname"));

                    if(user.has("location") && !user.isNull("location"))
                        etLocation.setText(user.getString("location"));

                    if(user.has("aboutme") && !user.isNull("aboutme"))
                        etAboutMe.setText(user.getString("aboutme"));
                } catch (JSONException ignored)
                {

                }

                // get info about linked services
                if(!user.has("linked_services") && user.isNull("linked_services"))
                    return;

                try {
                    linkedServices.clear();

                    JSONArray linked_services = user.getJSONArray("linked_services");
                    int numOfLinkedServices = linked_services.length();

                    for(int i = 0; i < numOfLinkedServices; i++)
                    {
                        JSONObject entry = linked_services.getJSONObject(i);

                        if(!entry.has("service") || entry.isNull("service") || !entry.has("linked") || entry.isNull("linked"))
                            continue;

                        JSONObject service = entry.getJSONObject("service");
                        if(!service.has("service_name") || service.isNull("service_name"))
                            continue;

                        linkedServices.put(service.getString("service_name"), entry.getBoolean("linked"));
                    }

                } catch (JSONException ignored) {

                }

                // check if the user has linked Strava external account
                Boolean linked = linkedServices.get("StravaGatewayService");

                if(linked != null)
                {
                    if(linked == Boolean.TRUE)
                    {
                        tvStravaStatus.setTextColor(ContextCompat.getColor(getApplicationContext(), R.color.acceptColor));
                        tvStravaStatus.setText(R.string.linked);
                    }
                    else
                    {
                        tvStravaStatus.setTextColor(ContextCompat.getColor(getApplicationContext(), R.color.rejectColor));
                        tvStravaStatus.setText(R.string.not_linked);
                    }
                }
            }
        });
    }

    public void UpdateProfile(View view)
    {
        int selectedIndex = priorityField.getSelectedItemPosition();
        if(selectedIndex != Spinner.INVALID_POSITION && selectedIndex != 0)
            SharedPrefSingleton.getInstance(getApplicationContext()).SetValue("string", "priority_field", priorityFieldsNames.get(selectedIndex));
        else
            SharedPrefSingleton.getInstance(getApplicationContext()).RemoveKey("priority_field");

        String surname = etSurname.getText().toString();
        String location = etLocation.getText().toString();
        String aboutme = etAboutMe.getText().toString();

        APIWrapper.UpdateUser(getApplicationContext(), surname, location, aboutme, new AbstractAPIResponseHandler() {
            @Override
            public void Handle(JSONObject response, int statusCode) throws JSONException {
                switch (statusCode)
                {
                    case HttpURLConnection.HTTP_OK:
                        RefreshUserAndRedirectToProfile();
                        break;
                    case HttpURLConnection.HTTP_UNAUTHORIZED:
                        ActivityHelper.Logout(getApplicationContext(), UpdateProfileActivity.this);
                        break;
                    default:
                        Toast.makeText(getApplicationContext(), APIWrapper.GetErrorMessageFromResponse(getApplicationContext(), response, statusCode), Toast.LENGTH_LONG).show();
                        break;
                }
            }
        });
    }

    private void RefreshUserAndRedirectToProfile() {
        APIObjectLoader.LoadData(getApplicationContext(), "user", "me", false, 10 * 60, null, new APIObjectLoader.APIObjectListener() {
            @Override
            public void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, APIObjectLoader.ErrorType errorCode) {
                if(errorCode == APIObjectLoader.ErrorType.AUTHORIZATION_FAILED)
                {
                    ActivityHelper.Logout(getApplicationContext(), UpdateProfileActivity.this);

                    return;
                }

                Intent intent = new Intent(UpdateProfileActivity.this, ProfileActivity.class);
                UpdateProfileActivity.this.startActivity(intent);
            }
        });
    }

    public void ExternalAccountSync(View view)
    {
        String service_name = null;

        switch(view.getId())
        {
            case R.id.btnStrava:
                service_name = "StravaGatewayService";
                break;
            default:
                break;
        }

        if(service_name == null)
            return;

        Boolean linked = linkedServices.get(service_name);
        if(linked == null)
            return;

        if(!linked)
        {
            // link external service

            APIObjectLoader.LoadData(getApplicationContext(), "authorizationParams", service_name, false, 0, null, new APIObjectLoader.APIObjectListener() {
                @Override
                public void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, APIObjectLoader.ErrorType errorCode) {
                    if(errorCode == APIObjectLoader.ErrorType.AUTHORIZATION_FAILED)
                    {
                        ActivityHelper.Logout(getApplicationContext(), UpdateProfileActivity.this);

                        return;
                    }

                    if(errorCode != APIObjectLoader.ErrorType.NO_ERROR || obj == null)
                        return;

                    if(obj.objectType != APIObjectCacheSingleton.EntryType.JSONOBJECT)
                        return;

                    JSONObject authorizationParams = (JSONObject) obj.cachedObject;
                    if(!authorizationParams.has("authorization_url") || authorizationParams.isNull("authorization_url"))
                        return;

                    try {
                        String authorizationUrl = authorizationParams.getString("authorization_url");

                        authorizationUrl = authorizationUrl.replace("location_url", GlobalVars.GetApiGatewayUrl());

                        Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(authorizationUrl));
                        UpdateProfileActivity.this.startActivity(intent);
                    } catch (JSONException ignored) {

                    }
                }
            });
        }
        else
        {
            // revoke external service

            APIWrapper.RevokeAuthorizationToExternalService(getApplicationContext(), service_name, new AbstractAPIResponseHandler() {
                @Override
                public void Handle(JSONObject response, int statusCode) throws JSONException {
                    switch(statusCode)
                    {
                        case HttpURLConnection.HTTP_OK:
                            Toast.makeText(getApplicationContext(), "External account revoked successfully", Toast.LENGTH_LONG).show();

                            Intent intent = new Intent(UpdateProfileActivity.this, ProfileActivity.class);
                            UpdateProfileActivity.this.startActivity(intent);
                            break;
                        case HttpURLConnection.HTTP_UNAUTHORIZED:
                            ActivityHelper.Logout(getApplicationContext(),UpdateProfileActivity.this);
                            break;
                        default:
                            Toast.makeText(getApplicationContext(), APIWrapper.GetErrorMessageFromResponse(getApplicationContext(), response, statusCode), Toast.LENGTH_LONG).show();
                            break;
                    }
                }
            });
        }
    }
}