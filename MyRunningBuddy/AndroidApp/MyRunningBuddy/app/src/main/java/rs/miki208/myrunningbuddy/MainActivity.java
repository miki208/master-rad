package rs.miki208.myrunningbuddy;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.widget.Toast;

import org.json.JSONException;
import org.json.JSONObject;

import java.net.HttpURLConnection;

import rs.miki208.myrunningbuddy.helpers.APIWrapper;
import rs.miki208.myrunningbuddy.helpers.AbstractAPIResponseHandler;
import rs.miki208.myrunningbuddy.helpers.SharedPrefSingleton;

public class MainActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
    }

    @Override
    protected void onResume()
    {
        super.onResume();

        // check if an access token is saved
        String accessToken = (String) SharedPrefSingleton
                .getInstance(getApplicationContext())
                .GetValue("string", getString(R.string.API_ACCESS_TOKEN));

        String refreshToken = (String) SharedPrefSingleton
                .getInstance(getApplicationContext())
                .GetValue("string", getString(R.string.API_REFRESH_TOKEN));

        long expiresAt = (Long) SharedPrefSingleton
                .getInstance(getApplicationContext())
                .GetValue("long", getString(R.string.API_EXPIRES_AT));

        if(accessToken == null || refreshToken == null || expiresAt == Long.MAX_VALUE)
        {
            // redirect to login
            Intent intent = new Intent(this, LoginActivity.class);
            startActivity(intent);
        }
        else if(System.currentTimeMillis() / 1000L > expiresAt)
        {
            // refresh access token and update shared pref
            Context thisActivity = this;

            APIWrapper.RefreshAccessToken(getApplicationContext(), refreshToken, new AbstractAPIResponseHandler() {
                @Override
                public void Handle(JSONObject response, int statusCode) throws JSONException {
                    switch(statusCode)
                    {
                        case HttpURLConnection.HTTP_OK:
                            // save authorization params
                            String access_token = response.getString("access_token");
                            String refresh_token = response.getString("refresh_token");
                            long expires_in = response.getLong("expires_in");

                            APIWrapper.SaveAuthorizationParams(getApplicationContext(), access_token, refresh_token, expires_in);

                            // redirect to profile
                            Intent intent = new Intent(thisActivity, ProfileActivity.class);
                            startActivity(intent);
                            break;
                        case HttpURLConnection.HTTP_UNAUTHORIZED:
                            Toast.makeText(getApplicationContext(), "Unexpected error", Toast.LENGTH_LONG).show();
                            break;
                        default:
                            Toast.makeText(getApplicationContext(), "Internal error", Toast.LENGTH_LONG).show();
                            break;
                    }
                }
            });
        }
        else
        {
            // redirect to profile
            Intent intent = new Intent(this, ProfileActivity.class);
            startActivity(intent);
        }
    }
}