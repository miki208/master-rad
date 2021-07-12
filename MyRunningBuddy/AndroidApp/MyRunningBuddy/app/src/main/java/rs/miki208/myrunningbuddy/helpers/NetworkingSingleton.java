package rs.miki208.myrunningbuddy.helpers;

import android.content.Context;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.toolbox.Volley;

public class NetworkingSingleton {
    private static NetworkingSingleton instance;
    private static Context context;

    private RequestQueue requestQueue;

    private NetworkingSingleton(Context context) {
        this.context = context;
        requestQueue = getRequestQueue();
    }

    public static synchronized NetworkingSingleton getInstance(Context context) {
        if (instance == null)
            instance = new NetworkingSingleton(context);

        return instance;
    }

    public RequestQueue getRequestQueue() {
        if (requestQueue == null)
            requestQueue = Volley.newRequestQueue(context.getApplicationContext());

        return requestQueue;
    }

    public <T> void addToRequestQueue(Request<T> req) {
        getRequestQueue().add(req);
    }
}
