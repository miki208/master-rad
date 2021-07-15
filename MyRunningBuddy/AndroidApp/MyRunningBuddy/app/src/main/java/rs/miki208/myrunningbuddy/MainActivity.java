package rs.miki208.myrunningbuddy;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Intent;
import android.os.Bundle;

import rs.miki208.myrunningbuddy.helpers.APIWrapper;

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

        // check if a session is saved
        String accessToken = APIWrapper.GetAccessToken(getApplicationContext());
        String refreshToken = APIWrapper.GetRefreshToken(getApplicationContext());
        long expiresAt = APIWrapper.GetExpiresAt(getApplicationContext());

        Intent intent;

        if(accessToken == null || refreshToken == null || expiresAt == Long.MAX_VALUE)
        {
            // redirect to login
            intent = new Intent(this, LoginActivity.class);
        }
        else
        {
            // redirect to profile
            intent = new Intent(this, ProfileActivity.class);
            intent.putExtra("user_id", "me");
        }

        startActivity(intent);
    }
}