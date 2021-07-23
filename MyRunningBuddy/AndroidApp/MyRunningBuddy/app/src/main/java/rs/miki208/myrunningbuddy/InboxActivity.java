package rs.miki208.myrunningbuddy;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import android.content.Context;
import android.os.Bundle;
import android.os.Handler;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.text.DateFormat;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;

import rs.miki208.myrunningbuddy.helpers.APIObjectCacheSingleton;
import rs.miki208.myrunningbuddy.helpers.APIObjectLoader;
import rs.miki208.myrunningbuddy.helpers.ActivityHelper;
import rs.miki208.myrunningbuddy.helpers.DownloadImageTask;

public class InboxActivity extends AppCompatActivity {
    public class Conversation
    {
        public String name;
        public String datetime;
        public String profileImageUrl;

        int userId;

        public boolean newConversation;
        public boolean newMessage;

        public Conversation()
        {
            name = "";
            datetime = "";
            profileImageUrl = "";

            userId = -1;

            newConversation = false;
            newMessage = false;
        }
    }

    public class ConversationAdapter extends ArrayAdapter<Conversation>
    {
        private int resourceLayout;
        private Context context;

        public ConversationAdapter(Context context, int resource, ArrayList<Conversation> items)
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

            Conversation elem = getItem(position);
            if(elem != null)
            {
                TextView tvName = view.findViewById(R.id.tvName);
                if(tvName != null)
                    tvName.setText(elem.name);

                ImageView ivProfilePhoto = view.findViewById(R.id.ivProfilePhoto);
                if(ivProfilePhoto != null && !elem.profileImageUrl.equals(""))
                    new DownloadImageTask(ivProfilePhoto, elem.profileImageUrl).execute();

                TextView tvConversationStatus = view.findViewById(R.id.tvConversationStatus);
                if(tvConversationStatus != null)
                {
                    String status = "";
                    if(elem.newConversation)
                        status += getString(R.string.new_match);
                    else if(elem.newMessage)
                        status += getString(R.string.new_message);

                    if(!status.equals(""))
                    {
                        tvConversationStatus.setTextColor(getResources().getColor(R.color.rejectColor));
                        status += "\n";
                    }
                    else
                        tvConversationStatus.setTextColor(getResources().getColor(R.color.black));

                    status += elem.datetime;

                    tvConversationStatus.setText(status);
                }
            }

            return view;
        }
    }

    final static int numOfConversationsPerPage = 10;

    ArrayList<Conversation> conversations;

    ArrayAdapter conversationsAdapter;

    Handler refreshMessageHandler;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_inbox);

        conversations = new ArrayList<>();

        conversationsAdapter = new ConversationAdapter(this,
                R.layout.conversations_listview, conversations);

        ListView listView = findViewById(R.id.lvConversations);
        listView.setAdapter(conversationsAdapter);

        refreshMessageHandler = new Handler();
        refreshMessageHandler.post(new Runnable() {
            @Override
            public void run() {
                String newerThan = null;
                if(!conversations.isEmpty())
                    newerThan = conversations.get(0).datetime;

                ReloadConversations(newerThan, null, this);
            }
        });
    }

    @Override
    protected void onResume() {
        super.onResume();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        refreshMessageHandler.removeCallbacksAndMessages(null);
    }

    private void ReloadConversations(String newerThanDt, String olderThanDt, Runnable context)
    {
        APIObjectLoader.LoadData(getApplicationContext(), "conversations", "me", false, 0, new APIObjectLoader.PaginationInfo(1, numOfConversationsPerPage, newerThanDt, olderThanDt), new APIObjectLoader.APIObjectListener() {
            @Override
            public void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, APIObjectLoader.ErrorType errorCode) {
                if(errorCode == APIObjectLoader.ErrorType.AUTHORIZATION_FAILED)
                {
                    ActivityHelper.Logout(getApplicationContext(), InboxActivity.this);

                    return;
                }

                if(errorCode != APIObjectLoader.ErrorType.NO_ERROR || obj == null)
                    return;

                if(obj.objectType != APIObjectCacheSingleton.EntryType.JSONOBJECT)
                    return;

                JSONObject response = (JSONObject) obj.cachedObject;
                if(!response.has("conversations") || response.isNull("conversations"))
                    return;

                ArrayList<Conversation> newConversations = new ArrayList<>();

                try {
                    JSONArray conversationsJson = response.getJSONArray("conversations");
                    int numOfConversations = conversationsJson.length();

                    for(int i = 0; i < numOfConversations; i++)
                    {
                        Conversation conversation = new Conversation();

                        JSONObject conversationJson = conversationsJson.getJSONObject(i);
                        if(conversationJson.has("user_info") && !conversationJson.isNull("user_info"))
                        {
                            JSONObject userInfo = conversationJson.getJSONObject("user_info");

                            if(!userInfo.has("id") || userInfo.isNull("id"))
                                continue;

                            conversation.userId = userInfo.getInt("id");

                            String name = "";
                            if(userInfo.has("name") && !userInfo.isNull("name"))
                                name += userInfo.getString("name");

                            if(userInfo.has("surname") && !userInfo.isNull("surname"))
                            {
                                String surname = userInfo.getString("surname");

                                if(!surname.equals(""))
                                    name += " " + surname.charAt(0) + ".";
                            }

                            conversation.name = name;

                            if(userInfo.has("profile_photo_url") && !userInfo.isNull("profile_photo_url"))
                                conversation.profileImageUrl = userInfo.getString("profile_photo_url");
                        }

                        if(!conversationJson.has("updated_at") || conversationJson.isNull("updated_at"))
                            continue;

                        conversation.datetime = conversationJson.getString("updated_at").split("\\.")[0];

                        if(conversation.userId == conversationJson.getInt("runner_id1"))
                        {
                            conversation.newMessage = conversationJson.getInt("runner_id2_seen_last_message") == 0;
                            conversation.newConversation = conversationJson.getInt("runner_id2_seen_conversation") == 0;
                        }
                        else
                        {
                            conversation.newMessage = conversationJson.getInt("runner_id1_seen_last_message") == 0;
                            conversation.newConversation = conversationJson.getInt("runner_id1_seen_conversation") == 0;
                        }

                        // remove existing conversations
                        int numOfExistingConversations = conversations.size();
                        for(int j = 0; j < numOfExistingConversations; j++)
                        {
                            if(conversations.get(j).userId == conversation.userId)
                            {
                                conversations.remove(j);
                                break;
                            }
                        }

                        // add conversation to temporary conversations
                        newConversations.add(conversation);
                    }
                } catch (JSONException e) {
                    return;
                }

                // "load newer" mode
                if(newerThanDt != null)
                    conversations.addAll(0, newConversations);
                else
                    conversations.addAll(newConversations);

                conversationsAdapter.notifyDataSetChanged();

                // schedule refreshing only if it's newerThan mode
                if(olderThanDt == null)
                    refreshMessageHandler.postDelayed(context,5000);
            }
        });
    }
}