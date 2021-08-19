package rs.miki208.myrunningbuddy;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.GravityCompat;
import androidx.drawerlayout.widget.DrawerLayout;

import android.content.Intent;
import android.os.Bundle;
import android.widget.TextView;

import org.json.JSONObject;

import java.util.HashMap;

import rs.miki208.myrunningbuddy.networking.api.APIObjectCacheSingleton;
import rs.miki208.myrunningbuddy.networking.api.APIObjectLoader;
import rs.miki208.myrunningbuddy.common.ActivityHelper;

public class ProfileActivity extends AppCompatActivity {
    String userId;

    HashMap<String, TextView> profileDataWidgets;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_profile);

        ActivityHelper.InitializeToolbarAndMenu(this);

        if(savedInstanceState != null)
            userId = savedInstanceState.getString("user_id");

        profileDataWidgets = new HashMap<>();
        ActivityHelper.FillProfileDataWidgets(this, profileDataWidgets);
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);

        setIntent(intent);
    }

    @Override
    protected void onResume() {
        super.onResume();

        String userIdIntent = getIntent().getStringExtra("user_id");
        if(userIdIntent != null && !userIdIntent.isEmpty())
            userId = userIdIntent;

        APIObjectLoader.LoadData(getApplicationContext(), "user", userId, true, 10 * 60, null, new APIObjectLoader.APIObjectListener() {
            @Override
            public void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, APIObjectLoader.ErrorType errorCode) {
                if(errorCode == APIObjectLoader.ErrorType.AUTHORIZATION_FAILED)
                {
                    Intent intent = new Intent(ProfileActivity.this, LoginActivity.class);
                    ProfileActivity.this.startActivity(intent);

                    ProfileActivity.this.finish();

                    return;
                }

                if(obj == null)
                    return;

                if(obj.objectType != APIObjectCacheSingleton.EntryType.JSONOBJECT)
                    return;

                JSONObject user = (JSONObject) obj.cachedObject;

                ActivityHelper.RenderProfile(ProfileActivity.this, profileDataWidgets, user);
            }
        });
    }

    @Override
    public void onSaveInstanceState(Bundle savedInstanceState) {
        super.onSaveInstanceState(savedInstanceState);

        savedInstanceState.putString("user_id", userId);
    }

    @Override
    public void onBackPressed() {
        DrawerLayout drawer = findViewById(R.id.drawer_layout);
        if(drawer.isDrawerOpen(GravityCompat.START))
            drawer.closeDrawer(GravityCompat.START);
        else
            super.onBackPressed();
    }
}