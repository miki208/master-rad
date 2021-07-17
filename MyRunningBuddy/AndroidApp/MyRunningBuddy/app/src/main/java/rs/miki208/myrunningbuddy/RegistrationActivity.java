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

import rs.miki208.myrunningbuddy.helpers.APIWrapper;
import rs.miki208.myrunningbuddy.helpers.AbstractAPIResponseHandler;

public class RegistrationActivity extends AppCompatActivity {
    EditText etEmail;
    EditText etPassword;
    EditText etName;
    EditText etSurname;
    EditText etLocation;
    EditText etAboutMe;
    Button btnSignUp;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_registration);

        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        etName = findViewById(R.id.etName);
        etSurname = findViewById(R.id.etSurname);
        etLocation = findViewById(R.id.etLocation);
        etAboutMe = findViewById(R.id.etAboutMe);
        btnSignUp = findViewById(R.id.btnSignUp);
    }

    public void onSignUpClick(View view)
    {
        btnSignUp.setEnabled(false);

        String email = etEmail.getText().toString();
        String password = etPassword.getText().toString();
        String name = etName.getText().toString();
        String surname = etSurname.getText().toString();
        String location = etLocation.getText().toString();
        String aboutme = etAboutMe.getText().toString();

        Context thisActivity = this;

        boolean success = APIWrapper.RegisterUser(getApplicationContext(), email, password, name, surname, location, aboutme, new AbstractAPIResponseHandler() {
            @Override
            public void Handle(JSONObject response, int statusCode) throws JSONException {
                switch (statusCode)
                {
                    case HttpURLConnection.HTTP_OK:
                        Toast.makeText(getApplicationContext(), getString(R.string.user_successfully_registered), Toast.LENGTH_LONG).show();

                        Intent intent = new Intent(thisActivity, LoginActivity.class);
                        startActivity(intent);
                        break;
                    case HttpURLConnection.HTTP_BAD_REQUEST:
                        String errorMessage = "";

                        try {
                            errorMessage = response.getString("message");
                        } catch(JSONException e) {
                            errorMessage = getString(R.string.internal_service_error);
                        }

                        Toast.makeText(getApplicationContext(), errorMessage, Toast.LENGTH_LONG).show();
                        break;
                    case HttpURLConnection.HTTP_INTERNAL_ERROR:
                        Toast.makeText(getApplicationContext(), getString(R.string.internal_service_unavailable), Toast.LENGTH_LONG).show();
                        break;
                    default:
                        Toast.makeText(getApplicationContext(), getString(R.string.unexpected_error), Toast.LENGTH_LONG).show();
                        break;
                }

                btnSignUp.setEnabled(true);
            }
        });

        if(!success)
            btnSignUp.setEnabled(true);
    }

    public void onSignInClick(View view)
    {
        Intent intent = new Intent(this, LoginActivity.class);
        startActivity(intent);
    }
}