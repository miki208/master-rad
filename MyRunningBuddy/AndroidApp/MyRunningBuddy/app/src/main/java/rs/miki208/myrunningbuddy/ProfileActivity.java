package rs.miki208.myrunningbuddy;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.widget.TextView;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.HashMap;

import rs.miki208.myrunningbuddy.helpers.APIObjectCacheSingleton;
import rs.miki208.myrunningbuddy.helpers.APIObjectLoader;
import rs.miki208.myrunningbuddy.helpers.ActivityHelper;

public class ProfileActivity extends AppCompatActivity {
    String userId;

    HashMap<String, TextView> profileDataWidgets;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_profile);

        userId = getIntent().getStringExtra("user_id");
        if(userId == null || userId.isEmpty())
        {
            if(savedInstanceState != null)
                userId = savedInstanceState.getString("user_id");
        }

        profileDataWidgets = new HashMap<>();
        profileDataWidgets.put("name", (TextView) findViewById(R.id.tvName));
        profileDataWidgets.put("location", (TextView) findViewById(R.id.tvLocation));
        profileDataWidgets.put("aboutme", (TextView) findViewById(R.id.tvAboutme));
        profileDataWidgets.put("avg_total_distance_per_week", (TextView) findViewById(R.id.tvAvgTotalDistancePerWeek));
        profileDataWidgets.put("avg_moving_time_per_week", (TextView) findViewById(R.id.tvAvgMovingTimePerWeek));
        profileDataWidgets.put("avg_longest_distance_per_week", (TextView) findViewById(R.id.tvAvgLongestDistancePerWeek));
        profileDataWidgets.put("avg_pace_per_week", (TextView) findViewById(R.id.tvAvgPacePerWeek));
        profileDataWidgets.put("avg_total_elevation_per_week", (TextView) findViewById(R.id.tvAvgTotalElevationPerWeek));
        profileDataWidgets.put("avg_start_time_per_week", (TextView) findViewById(R.id.tvAvgStartTimePerWeek));
    }

    @Override
    protected void onResume() {
        super.onResume();

        Context thisActivity = this;
        APIObjectLoader.LoadData(getApplicationContext(), "user", userId, true, 10 * 60, new APIObjectLoader.PaginationInfo(), new APIObjectLoader.APIObjectListener() {
            @Override
            public void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, boolean authorizationErrors) {
                if(authorizationErrors)
                {
                    Intent intent = new Intent(thisActivity, LoginActivity.class);
                    thisActivity.startActivity(intent);
                }

                if(obj == null)
                    return;

                if(obj.objectType == APIObjectCacheSingleton.EntryType.JSONARRAY)
                    return;

                JSONObject user = (JSONObject) obj.cachedObject;

                ActivityHelper.RenderProfile(thisActivity, profileDataWidgets, user);
            }
        });
    }
}