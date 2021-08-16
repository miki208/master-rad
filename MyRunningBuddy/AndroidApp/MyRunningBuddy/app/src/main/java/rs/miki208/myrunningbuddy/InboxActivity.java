package rs.miki208.myrunningbuddy;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.GravityCompat;
import androidx.drawerlayout.widget.DrawerLayout;

import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;

import rs.miki208.myrunningbuddy.networking.api.APIObjectCacheSingleton;
import rs.miki208.myrunningbuddy.networking.api.APIObjectLoader;
import rs.miki208.myrunningbuddy.common.ActivityHelper;
import rs.miki208.myrunningbuddy.networking.DownloadImageTask;

public class InboxActivity extends AppCompatActivity {
    public class Conversation
    {
        public String name;
        public String datetime;
        public String profileImageUrl;

        int userId;

        public boolean newConversation;
        public boolean newMessage;

        boolean loadMoreItem;

        public Conversation()
        {
            name = "";
            datetime = "";
            profileImageUrl = "";

            userId = -1;

            newConversation = false;
            newMessage = false;

            loadMoreItem = false;
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

        private void SetLoadMore(View view, boolean visible)
        {
            TextView tvLoadMore = view.findViewById(R.id.tvLoadMore);
            if(tvLoadMore != null) {
                if(visible)
                    tvLoadMore.setVisibility(View.VISIBLE);
                else
                    tvLoadMore.setVisibility(View.GONE);
            }
        }

        private void SetConversationView(View view, boolean visible)
        {
            ImageView ivProfilePhoto = view.findViewById(R.id.ivProfilePhoto);
            if(ivProfilePhoto != null) {
                if (visible)
                    ivProfilePhoto.setVisibility(View.VISIBLE);
                else
                    ivProfilePhoto.setVisibility(View.INVISIBLE);
            }

            TextView tvName = view.findViewById(R.id.tvName);
            if(tvName != null) {
                if (visible)
                    tvName.setVisibility(View.VISIBLE);
                else
                    tvName.setVisibility(View.INVISIBLE);
            }

            TextView tvConversationStatus = view.findViewById(R.id.tvConversationStatus);
            if(tvConversationStatus != null) {
                if (visible)
                    tvConversationStatus.setVisibility(View.VISIBLE);
                else
                    tvConversationStatus.setVisibility(View.INVISIBLE);
            }
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
            if(elem != null) {
                if (elem.loadMoreItem) {
                    SetLoadMore(view, true);
                    SetConversationView(view, false);
                } else {
                    SetLoadMore(view, false);
                    SetConversationView(view, true);

                    // set name
                    TextView tvName = view.findViewById(R.id.tvName);
                    if (tvName != null)
                        tvName.setText(elem.name);

                    // set photo
                    ImageView ivProfilePhoto = view.findViewById(R.id.ivProfilePhoto);
                    if (ivProfilePhoto != null && !elem.profileImageUrl.equals(""))
                        new DownloadImageTask(ivProfilePhoto, elem.profileImageUrl).execute();

                    // set conversation status (conversation status + date)
                    TextView tvConversationStatus = view.findViewById(R.id.tvConversationStatus);
                    if (tvConversationStatus != null) {
                        String status = "";

                        // add status if the conversation is a new one, or if there is an unseen message
                        if (elem.newConversation)
                            status += getString(R.string.new_match);
                        else if (elem.newMessage)
                            status += getString(R.string.new_message);

                        // if there isn't a special status, color of the status message is black, otherwise red
                        if (!status.equals("")) {
                            tvConversationStatus.setTextColor(getResources().getColor(R.color.rejectColor));
                            status += "\n";
                        } else
                            tvConversationStatus.setTextColor(getResources().getColor(R.color.black));

                        // add date (it should be local time zone)
                        status += elem.datetime;

                        tvConversationStatus.setText(status);
                    }
                }
            }

            return view;
        }
    }

    final static int numOfConversationsPerPage = 10;

    ArrayList<Conversation> conversations;
    ArrayAdapter conversationsAdapter;
    Handler refreshConversationHandler;

    boolean activityInPauseState;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_inbox);

        ActivityHelper.InitializeToolbarAndMenu(this);

        conversations = new ArrayList<>();

        // add "load more" item at the bottom
        Conversation loadMoreItem = new Conversation();
        loadMoreItem.loadMoreItem = true;
        conversations.add(loadMoreItem);

        conversationsAdapter = new ConversationAdapter(this,
                R.layout.conversations_listview, conversations);

        ListView listView = findViewById(R.id.lvConversations);
        listView.setAdapter(conversationsAdapter);
        listView.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            public void onItemClick(AdapterView<?> parent, View view, int position, long id) {
                int numOfConversations = conversations.size();

                Conversation conversation = conversations.get(position);
                if(conversation.loadMoreItem)
                {
                    String olderThan = null;
                    if(numOfConversations > 1)
                        olderThan = conversations.get(numOfConversations - 2).datetime;

                    ReloadConversations(null, olderThan, null);
                }
                else
                {
                    Intent intent = new Intent(InboxActivity.this, ChatActivity.class);
                    intent.putExtra("user_id", conversation.userId);
                    intent.putExtra("name", conversation.name);

                    InboxActivity.this.startActivity(intent);
                }
            }
        });
        listView.setLongClickable(true);
        listView.setOnItemLongClickListener(new AdapterView.OnItemLongClickListener() {
            @Override
            public boolean onItemLongClick(AdapterView<?> parent, View view, int position, long id) {
                Conversation conversation = conversations.get(position);

                if(conversation.loadMoreItem)
                    return true;

                Intent intent = new Intent(InboxActivity.this, ProfileActivity.class);
                intent.putExtra("user_id", String.valueOf(conversation.userId));

                InboxActivity.this.startActivity(intent);

                return true;
            }
        });

        refreshConversationHandler = new Handler();
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

        refreshConversationHandler.post(new Runnable() {
            @Override
            public void run() {
                String newerThan = null;
                if(conversations.size() > 1)
                    newerThan = conversations.get(1).datetime;

                ReloadConversations(newerThan, null, this);
            }
        });
    }

    @Override
    protected void onPause() {
        super.onPause();

        activityInPauseState = true;

        refreshConversationHandler.removeCallbacksAndMessages(null);
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

                        if(!conversationJson.has("last_change_at") || conversationJson.isNull("last_change_at"))
                            continue;

                        conversation.datetime = conversationJson.getString("last_change_at").split("\\.")[0];

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
                        int numOfExistingConversations = conversations.size() - 1;
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
                    conversations.addAll(conversations.size() - 1, newConversations);

                conversationsAdapter.notifyDataSetChanged();

                // schedule refreshing only if it's "newer than" mode
                if(olderThanDt == null && context != null && !activityInPauseState)
                    refreshConversationHandler.postDelayed(context,5000);
            }
        });
    }
}