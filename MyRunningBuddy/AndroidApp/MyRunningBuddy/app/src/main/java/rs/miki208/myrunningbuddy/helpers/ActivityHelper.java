package rs.miki208.myrunningbuddy.helpers;

import android.content.Context;
import android.widget.TextView;

import org.json.JSONObject;

import java.util.Date;
import java.util.HashMap;
import java.util.TimeZone;

public class ActivityHelper {
    public static void RenderProfile(Context activity, HashMap<String, TextView> profileDataWidgets, JSONObject user)
    {
        try
        {
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
            if(user.has("location") && !user.isNull("location"))
                location = "@ " + user.getString("location");

            ((TextView) profileDataWidgets.get("location")).setText(location);

            String aboutMe = "";
            if(user.has("aboutme") && !user.isNull("aboutme"))
                location = user.getString("aboutme");

            ((TextView) profileDataWidgets.get("aboutme")).setText(location);

            String avgTotalDistancePerWeek = "Avg. total distance per week: ";
            String avgMovingTimePerWeek = "Avg. moving time per week: ";
            String avgLongestDistancePerWeek = "Avg. longest distance per week: ";
            String avgPacePerWeek = "Avg. pace per week: ";
            String avgTotalElevationPerWeek = "Avg. total elevation per week: ";
            String avgStartTimePerWeek = "Avg. start time per week: ";

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
}
