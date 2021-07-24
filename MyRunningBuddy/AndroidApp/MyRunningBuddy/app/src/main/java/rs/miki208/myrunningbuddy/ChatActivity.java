package rs.miki208.myrunningbuddy;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.GravityCompat;
import androidx.drawerlayout.widget.DrawerLayout;

import android.content.Context;
import android.os.Bundle;
import android.os.Handler;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.EditText;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.net.HttpURLConnection;
import java.util.ArrayList;

import rs.miki208.myrunningbuddy.helpers.APIObjectCacheSingleton;
import rs.miki208.myrunningbuddy.helpers.APIObjectLoader;
import rs.miki208.myrunningbuddy.helpers.APIWrapper;
import rs.miki208.myrunningbuddy.helpers.AbstractAPIResponseHandler;
import rs.miki208.myrunningbuddy.helpers.ActivityHelper;

public class ChatActivity extends AppCompatActivity {

    public class Message
    {
        public String name;
        public String datetime;
        public String message;

        public int userId;

        public boolean loadMoreItem;

        public Message()
        {
            name = "";
            datetime = "";
            message = "";

            userId = -1;

            loadMoreItem = false;
        }
    }

    public class MessageAdapter extends ArrayAdapter<Message>
    {
        private int resourceLayout;
        private Context context;

        public MessageAdapter(Context context, int resource, ArrayList<ChatActivity.Message> items)
        {
            super(context, resource, items);

            this.resourceLayout = resource;
            this.context = context;
        }

        @NonNull
        @Override
        public View getView(int position, @Nullable View convertView, @NonNull ViewGroup parent) {
            View view = convertView;

            if(view == null)
            {
                LayoutInflater layoutInflater = LayoutInflater.from(context);
                view = layoutInflater.inflate(resourceLayout, null);
            }

            Message item = getItem(position);
            if(item != null)
            {
                TextView tvLoadMore = view.findViewById(R.id.tvLoadMore);
                TextView tvDatetime = view.findViewById(R.id.tvDatetime);
                TextView tvLeftMessage = view.findViewById(R.id.tvLeftMessage);
                TextView tvRightMessage = view.findViewById(R.id.tvRightMessage);

                if (item.loadMoreItem) {

                    if(tvLoadMore != null)
                        tvLoadMore.setVisibility(View.VISIBLE);
                    if(tvDatetime != null)
                        tvDatetime.setVisibility(View.GONE);
                    if(tvLeftMessage != null)
                        tvLeftMessage.setVisibility(View.GONE);
                    if(tvRightMessage != null)
                        tvRightMessage.setVisibility(View.GONE);
                }
                else
                {
                    if(tvLoadMore != null)
                        tvLoadMore.setVisibility(View.GONE);

                    if(tvDatetime != null)
                    {
                        tvDatetime.setText(item.datetime);
                        tvDatetime.setVisibility(View.VISIBLE);
                    }

                    if(tvLeftMessage != null && tvRightMessage != null)
                    {
                        if(item.userId == ChatActivity.this.userId)  // left message box
                        {
                            tvLeftMessage.setVisibility(View.VISIBLE);
                            tvRightMessage.setVisibility(View.GONE);

                            tvLeftMessage.setText(item.message);
                        }
                        else
                        {
                            tvLeftMessage.setVisibility(View.GONE);
                            tvRightMessage.setVisibility(View.VISIBLE);

                            tvRightMessage.setText(item.message);
                        }
                    }
                }
            }

            return view;
        }
    }

    final static int numOfMessagesPerPage = 20;

    ArrayList<ChatActivity.Message> messages;
    ArrayAdapter messagesAdapter;
    Handler refreshMessagesHandler;

    ListView listView;
    EditText etMessage;

    int userId;
    String name;

    boolean activityInPauseState;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_chat);

        ActivityHelper.InitializeToolbarAndMenu(this);

        userId = getIntent().getIntExtra("user_id", -1);
        if(userId == -1 && savedInstanceState != null)
            userId = savedInstanceState.getInt("user_id");

        name = getIntent().getStringExtra("name");
        if(name == null && savedInstanceState != null)
            name = savedInstanceState.getString("name");

        etMessage = findViewById(R.id.etMessage);
        etMessage.setHint(getString(R.string.send_message_to) + " " + name);

        messages = new ArrayList<>();

        // add "load more" item at the top
        Message loadMoreItem = new Message();
        loadMoreItem.loadMoreItem = true;
        messages.add(loadMoreItem);

        messagesAdapter = new MessageAdapter(this, R.layout.messages_listview, messages);

        listView = findViewById(R.id.lvMessages);
        listView.setDivider(null);
        listView.setAdapter(messagesAdapter);
        listView.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            public void onItemClick(AdapterView<?> parent, View view, int position, long id) {
                int numOfMessages = messages.size();

                Message message = messages.get(position);
                if(!message.loadMoreItem)
                    return;

                String olderThan = null;
                if(numOfMessages > 1)
                    olderThan = messages.get(1).datetime;

                ReloadMessages(null, olderThan, null);
            }
        });

        refreshMessagesHandler = new Handler();
    }

    public void SendMessage(View view)
    {
        if(userId <= 0)
            return;

        String message = etMessage.getText().toString();
        if(message.trim().equals(""))
            return;

        APIWrapper.SendMessage(getApplicationContext(), String.valueOf(userId), message, new AbstractAPIResponseHandler() {
            @Override
            public void Handle(JSONObject response, int statusCode) throws JSONException {
                if(statusCode != HttpURLConnection.HTTP_OK)
                {
                    Toast.makeText(getApplicationContext(), APIWrapper.GetErrorMessageFromResponse(getApplicationContext(), response, statusCode), Toast.LENGTH_LONG).show();

                    if(statusCode == HttpURLConnection.HTTP_UNAUTHORIZED)
                        ActivityHelper.Logout(getApplicationContext(), ChatActivity.this);

                    return;
                }

                etMessage.setText("");
            }
        });
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

        activityInPauseState = false;

        refreshMessagesHandler.post(new Runnable() {
            @Override
            public void run() {
                int numOfMessages = messages.size();

                String newerThan = null;
                if(numOfMessages > 1)
                    newerThan = messages.get(numOfMessages - 1).datetime;

                ReloadMessages(newerThan, null, this);
            }
        });
    }

    @Override
    protected void onPause() {
        super.onPause();

        activityInPauseState = true;
        refreshMessagesHandler.removeCallbacksAndMessages(null);
    }

    @Override
    public void onSaveInstanceState(Bundle savedInstanceState) {
        super.onSaveInstanceState(savedInstanceState);

        savedInstanceState.putInt("user_id", userId);
        savedInstanceState.putString("name", name);
    }

    private void ReloadMessages(String newerThanDt, String olderThanDt, Runnable context)
    {
        if(userId <= 0)
            return;

        APIObjectLoader.LoadData(getApplicationContext(), "messages", String.valueOf(userId), false, 0, new APIObjectLoader.PaginationInfo(1, numOfMessagesPerPage, newerThanDt, olderThanDt), new APIObjectLoader.APIObjectListener() {
            @Override
            public void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, APIObjectLoader.ErrorType errorCode) {
                if (errorCode == APIObjectLoader.ErrorType.AUTHORIZATION_FAILED) {
                    ActivityHelper.Logout(getApplicationContext(), ChatActivity.this);

                    return;
                }

                if (errorCode != APIObjectLoader.ErrorType.NO_ERROR || obj == null)
                    return;

                if (obj.objectType != APIObjectCacheSingleton.EntryType.JSONOBJECT)
                    return;

                JSONObject response = (JSONObject) obj.cachedObject;
                if (!response.has("messages") || response.isNull("messages"))
                    return;

                try {
                    JSONArray messagesJson = response.getJSONArray("messages");
                    int numOfMessages = messagesJson.length();

                    for(int i = numOfMessages - 1; i >= 0; i--)
                    {
                        Message message = new Message();

                        JSONObject messageJson = messagesJson.getJSONObject(i);

                        if(messageJson.has("runner_id") && !messageJson.isNull("runner_id"))
                            message.userId = messageJson.getInt("runner_id");
                        else
                            continue;

                        if(messageJson.has("message") && !messageJson.isNull("message"))
                            message.message = messageJson.getString("message");
                        else
                            continue;

                        if(messageJson.has("updated_at") && !messageJson.isNull("updated_at"))
                            message.datetime = messageJson.getString("updated_at").split("\\.")[0];
                        else
                            continue;

                        message.name = name;

                        if(newerThanDt != null)
                            messages.add(message);
                        else
                            messages.add(numOfMessages - i, message);
                    }

                    if(numOfMessages != 0)
                    {
                        messagesAdapter.notifyDataSetChanged();

                        // scroll to bottom if this is the first time messages are loaded
                        // or if messages are loaded in "newer than" mode
                        if(olderThanDt == null)
                            listView.setSelection(messages.size() - 1);
                    }

                    // schedule refreshing only if it's "newer than" mode
                    if(olderThanDt == null && context != null && !activityInPauseState)
                        refreshMessagesHandler.postDelayed(context, 1000);
                } catch (JSONException e) {
                    return;
                }
            }
        });
    }
}