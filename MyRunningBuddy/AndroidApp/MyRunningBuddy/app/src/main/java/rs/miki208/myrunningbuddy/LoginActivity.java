package rs.miki208.myrunningbuddy;

import androidx.appcompat.app.AppCompatActivity;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

import org.json.JSONException;
import org.json.JSONObject;

import java.net.HttpURLConnection;

import rs.miki208.myrunningbuddy.helpers.APIObjectCacheSingleton;
import rs.miki208.myrunningbuddy.helpers.APIWrapper;
import rs.miki208.myrunningbuddy.helpers.AbstractAPIResponseHandler;

public class LoginActivity extends AppCompatActivity {
    EditText etEmail;
    EditText etPassword;
    Button btnSignIn;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        btnSignIn = findViewById(R.id.btnSignIn);
    }

    @Override
    protected void onResume() {
        super.onResume();
        
        APIObjectCacheSingleton.getInstance().RemoveAll();
    }

    public void onSignUpClick(View view)
    {
        Intent intent = new Intent(this, RegistrationActivity.class);
        startActivity(intent);
    }

    public void onSignInClick(View view)
    {
        btnSignIn.setEnabled(false);

        String email = etEmail.getText().toString();
        String password = etPassword.getText().toString();

        Context thisActivity = this;

        APIWrapper.GetAccessToken(getApplicationContext(), email, password, new AbstractAPIResponseHandler() {
            @Override
            public void Handle(JSONObject response, int statusCode) throws JSONException {
                switch(statusCode)
                {
                    case HttpURLConnection.HTTP_OK:
                        String access_token = response.getString("access_token");
                        String refresh_token = response.getString("refresh_token");
                        long expires_in = response.getLong("expires_in");

                        APIWrapper.SaveAuthorizationParams(getApplicationContext(), access_token, refresh_token, expires_in);

                        // redirect to profile
                        Intent intent = new Intent(thisActivity, ProfileActivity.class);
                        intent.putExtra("user_id", "me");

                        thisActivity.startActivity(intent);
                        break;
                    case HttpURLConnection.HTTP_UNAUTHORIZED:
                        Toast.makeText(getApplicationContext(), "Unexpected error", Toast.LENGTH_LONG).show();
                        break;
                    case HttpURLConnection.HTTP_BAD_REQUEST:
                        Toast.makeText(getApplicationContext(), "Wrong email or password", Toast.LENGTH_LONG).show();
                        break;
                    default:
                        Toast.makeText(getApplicationContext(), "Internal error", Toast.LENGTH_LONG).show();
                        break;
                }

                btnSignIn.setEnabled(true);
            }
        });
    }
}