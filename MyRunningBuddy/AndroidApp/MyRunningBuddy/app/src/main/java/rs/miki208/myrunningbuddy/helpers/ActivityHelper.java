package rs.miki208.myrunningbuddy.helpers;

import android.content.Context;
import android.content.Intent;
import android.util.Pair;
import android.view.MenuItem;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.appcompat.app.ActionBarDrawerToggle;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.core.view.GravityCompat;
import androidx.drawerlayout.widget.DrawerLayout;

import com.android.volley.toolbox.NetworkImageView;
import com.google.android.material.navigation.NavigationView;

import org.json.JSONObject;

import java.util.Date;
import java.util.HashMap;
import java.util.TimeZone;

import rs.miki208.myrunningbuddy.InboxActivity;
import rs.miki208.myrunningbuddy.LoginActivity;
import rs.miki208.myrunningbuddy.MatchingActivity;
import rs.miki208.myrunningbuddy.ProfileActivity;
import rs.miki208.myrunningbuddy.R;
import rs.miki208.myrunningbuddy.UpdateProfileActivity;

public class ActivityHelper {
    public static void FillProfileDataWidgets(AppCompatActivity activity, HashMap<String, TextView> profileDataWidgets)
    {
        profileDataWidgets.put("name", (TextView) activity.findViewById(R.id.tvName));
        profileDataWidgets.put("location", (TextView) activity.findViewById(R.id.tvLocation));
        profileDataWidgets.put("aboutme", (TextView) activity.findViewById(R.id.tvAboutme));
        profileDataWidgets.put("avg_total_distance_per_week", (TextView) activity.findViewById(R.id.tvAvgTotalDistancePerWeek));
        profileDataWidgets.put("avg_moving_time_per_week", (TextView) activity.findViewById(R.id.tvAvgMovingTimePerWeek));
        profileDataWidgets.put("avg_longest_distance_per_week", (TextView) activity.findViewById(R.id.tvAvgLongestDistancePerWeek));
        profileDataWidgets.put("avg_pace_per_week", (TextView) activity.findViewById(R.id.tvAvgPacePerWeek));
        profileDataWidgets.put("avg_total_elevation_per_week", (TextView) activity.findViewById(R.id.tvAvgTotalElevationPerWeek));
        profileDataWidgets.put("avg_start_time_per_week", (TextView) activity.findViewById(R.id.tvAvgStartTimePerWeek));
    }

    public static void RenderProfile(AppCompatActivity activity, HashMap<String, TextView> profileDataWidgets, JSONObject user)
    {
        try
        {
            String profilePhotoUrl = null;
            if(user.has("profile_photo_url") && !user.isNull("profile_photo_url"))
                profilePhotoUrl = user.getString("profile_photo_url");

            ImageView ivProfilePhoto = activity.findViewById(R.id.ivProfilePhoto);
            if(profilePhotoUrl != null && !profilePhotoUrl.equals(""))
                new DownloadImageTask(ivProfilePhoto, profilePhotoUrl).execute();

            String name = null;
            if(user.has("name"))
                name = user.getString("name");

            String surname = null;
            if(user.has("surname"))
                surname = user.getString("surname");

            if(name != null)
            {
                if(surname != null && !surname.isEmpty())
                    name = name + " " + surname.charAt(0) + ".";

                ((TextView) profileDataWidgets.get("name")).setText(name);
            }

            String location = "";
            if(user.has("location") && !user.isNull("location") && !user.getString("location").equals(""))
                location = "@ " + user.getString("location");

            ((TextView) profileDataWidgets.get("location")).setText(location);

            String aboutMe = "";
            if(user.has("aboutme") && !user.isNull("aboutme"))
                location = user.getString("aboutme");

            ((TextView) profileDataWidgets.get("aboutme")).setText(location);

            String avgTotalDistancePerWeek = activity.getString(R.string.avg_total_distance_per_week) + ": ";
            String avgMovingTimePerWeek = activity.getString(R.string.avg_moving_time_per_week) + ": ";
            String avgLongestDistancePerWeek = activity.getString(R.string.avg_longest_distance_per_week) + ": ";
            String avgPacePerWeek = activity.getString(R.string.avg_pace_per_week) + ": ";
            String avgTotalElevationPerWeek = activity.getString(R.string.avg_total_elevation_per_week) + ": ";
            String avgStartTimePerWeek = activity.getString(R.string.avg_start_time_per_week) + ": ";

            if(user.has("stats") && !user.isNull("stats"))
            {
                JSONObject runnerStatsRoot = user.getJSONObject("stats");
                if(runnerStatsRoot.has("stats") && !runnerStatsRoot.isNull("stats"))
                {
                    JSONObject runnerStats = runnerStatsRoot.getJSONObject("stats");

                    if(runnerStats.has("avg_total_distance_per_week") && !runnerStats.isNull("avg_total_distance_per_week"))
                    {
                        double fAvgTotalDistancePerWeek = runnerStats.getDouble("avg_total_distance_per_week");

                        avgTotalDistancePerWeek += String.format("%.2f", fAvgTotalDistancePerWeek) + " km";
                    }

                    if(runnerStats.has("avg_moving_time_per_week") && !runnerStats.isNull("avg_moving_time_per_week"))
                    {
                        double fAvgMovingTimePerWeek = runnerStats.getDouble("avg_moving_time_per_week");
                        int hours = (int) (fAvgMovingTimePerWeek / 3600.0);
                        int minutes = (int) ((fAvgMovingTimePerWeek % 3600.0) / 60.0);

                        avgMovingTimePerWeek += hours + "h " + minutes + "min";
                    }

                    if(runnerStats.has("avg_longest_distance_per_week") && !runnerStats.isNull("avg_longest_distance_per_week"))
                    {
                        double fAvgLongestDistancePerWeek = runnerStats.getDouble("avg_longest_distance_per_week");

                        avgLongestDistancePerWeek += String.format("%.2f", fAvgLongestDistancePerWeek) + " km";
                    }

                    if(runnerStats.has("avg_pace_per_week") && !runnerStats.isNull("avg_pace_per_week"))
                    {
                        double fAvgPacePerWeek = runnerStats.getDouble("avg_pace_per_week");

                        int minutes = (int) Math.floor(fAvgPacePerWeek);
                        int seconds = (int) Math.floor((fAvgPacePerWeek - minutes) * 60.0);

                        avgPacePerWeek += minutes + ":" + String.format("%02d", seconds) + " /km";
                    }

                    if(runnerStats.has("avg_total_elevation_per_week") && !runnerStats.isNull("avg_total_elevation_per_week"))
                    {
                        double fAvgTotalElevationPerWeek = runnerStats.getDouble("avg_total_elevation_per_week");

                        avgTotalElevationPerWeek += String.format("%.2f", fAvgTotalElevationPerWeek) + " m";
                    }

                    if(runnerStats.has("avg_start_time_per_week") && !runnerStats.isNull("avg_start_time_per_week"))
                    {
                        double fAvgStartTimePerWeek = runnerStats.getDouble("avg_start_time_per_week") + TimeZone.getDefault().getOffset(System.currentTimeMillis()) / 1000.0;

                        if(fAvgStartTimePerWeek < 0)
                            fAvgStartTimePerWeek = 24 * 3600 + fAvgStartTimePerWeek;

                        int hours = (int) (fAvgStartTimePerWeek / 3600.0) % 24;
                        int minutes = (int) ((fAvgStartTimePerWeek % 3600.0) / 60.0);

                        avgStartTimePerWeek += String.format("%02d:%02d", hours, minutes);
                    }
                }
            }

            ((TextView) profileDataWidgets.get("avg_total_distance_per_week")).setText(avgTotalDistancePerWeek);
            ((TextView) profileDataWidgets.get("avg_moving_time_per_week")).setText(avgMovingTimePerWeek);
            ((TextView) profileDataWidgets.get("avg_longest_distance_per_week")).setText(avgLongestDistancePerWeek);
            ((TextView) profileDataWidgets.get("avg_pace_per_week")).setText(avgPacePerWeek);
            ((TextView) profileDataWidgets.get("avg_total_elevation_per_week")).setText(avgTotalElevationPerWeek);
            ((TextView) profileDataWidgets.get("avg_start_time_per_week")).setText(avgStartTimePerWeek);

        }
        catch(Exception ignore)
        {
            ignore.printStackTrace();;
        }
    }

    public static void RenderClearProfile(AppCompatActivity activity, HashMap<String, TextView> profileDataWidgets)
    {
        ((TextView) profileDataWidgets.get("name")).setText("");
        ((TextView) profileDataWidgets.get("aboutme")).setText("");
        ((TextView) profileDataWidgets.get("location")).setText("");

        ((TextView) profileDataWidgets.get("avg_total_distance_per_week")).setText("");
        ((TextView) profileDataWidgets.get("avg_moving_time_per_week")).setText("");
        ((TextView) profileDataWidgets.get("avg_longest_distance_per_week")).setText("");
        ((TextView) profileDataWidgets.get("avg_pace_per_week")).setText("");
        ((TextView) profileDataWidgets.get("avg_total_elevation_per_week")).setText("");
        ((TextView) profileDataWidgets.get("avg_start_time_per_week")).setText("");

        ((ImageView) activity.findViewById(R.id.ivProfilePhoto)).setImageResource(R.drawable.no_profile_image);
    }

    public static void InitializeToolbarAndMenu(AppCompatActivity activity)
    {
        Toolbar toolbar = (Toolbar) activity.findViewById(R.id.toolbar);
        activity.setSupportActionBar(toolbar);

        DrawerLayout drawer = (DrawerLayout) activity.findViewById(R.id.drawer_layout);
        ActionBarDrawerToggle toggle = new ActionBarDrawerToggle(activity, drawer, toolbar, R.string.open_nav_drawer, R.string.close_nav_drawer);
        drawer.addDrawerListener(toggle);
        toggle.syncState();

        NavigationView navigationView = (NavigationView) activity.findViewById(R.id.nav_view);
        navigationView.setNavigationItemSelectedListener(new NavigationView.OnNavigationItemSelectedListener() {
            @Override
            public boolean onNavigationItemSelected(@NonNull MenuItem item) {
                Intent intent = null;

                switch (item.getItemId())
                {
                    case R.id.nav_profile:
                        intent = new Intent(activity, ProfileActivity.class);
                        intent.putExtra("user_id", "me");
                        break;
                    case R.id.nav_find_runner:
                        intent = new Intent(activity, MatchingActivity.class);
                        break;
                    case R.id.nav_update_profile:
                        intent = new Intent(activity, UpdateProfileActivity.class);
                        break;
                    case R.id.nav_inbox:
                        intent = new Intent(activity, InboxActivity.class);
                        break;
                    default:
                        break;
                }

                if(intent != null)
                    activity.startActivity(intent);

                drawer.closeDrawer(GravityCompat.START);

                return true;
            }
        });
    }

    public static void Logout(Context appCtx, AppCompatActivity activity)
    {
        SharedPrefSingleton.getInstance(appCtx).RemoveKey(appCtx.getString(R.string.API_ACCESS_TOKEN));
        SharedPrefSingleton.getInstance(appCtx).RemoveKey(appCtx.getString(R.string.API_REFRESH_TOKEN));
        SharedPrefSingleton.getInstance(appCtx).RemoveKey(appCtx.getString(R.string.API_EXPIRES_AT));

        Intent intent = new Intent(activity, LoginActivity.class);

        activity.startActivity(intent);

        activity.finish();
    }
}
