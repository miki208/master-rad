package rs.miki208.myrunningbuddy.helpers;

import android.content.Context;
import android.widget.Toast;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.ServerError;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.HttpHeaderParser;
import com.android.volley.toolbox.JsonObjectRequest;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.UnsupportedEncodingException;

import rs.miki208.myrunningbuddy.R;

public class APIWrapper {
    private static class DefaultResponseListener implements Response.Listener<JSONObject>, Response.ErrorListener
    {
        private AbstractAPIResponseHandler handler = null;

        DefaultResponseListener(AbstractAPIResponseHandler handler)
        {
            this.handler = handler;
        }

        @Override
        public void onResponse(JSONObject response) {
            try {
                handler.Handle(response, 200);
            } catch (JSONException ignored) {

            }
        }

        @Override
        public void onErrorResponse(VolleyError error) {
            if(error instanceof ServerError || error instanceof AuthFailureError)
            {
                try {
                    JSONObject response = new JSONObject(new String(error.networkResponse.data, HttpHeaderParser.parseCharset(error.networkResponse.headers)));

                    handler.Handle(response, error.networkResponse.statusCode);
                } catch (JSONException | UnsupportedEncodingException e) {
                    try {
                        handler.Handle(null, -1);
                    } catch (JSONException ignored) {

                    }
                }
            }
            else
            {
                try {
                    handler.Handle(null, -1);
                } catch (JSONException ignored) {

                }
            }
        }
    }

    private static int StringMethodToEnum(String method)
    {
        switch (method)
        {
            case "GET":
                return Request.Method.GET;
            case "POST":
                return Request.Method.POST;
            case "PATCH":
                return Request.Method.PATCH;
            case "DELETE":
                return Request.Method.DELETE;
            default:
                return Request.Method.DEPRECATED_GET_OR_POST;
        }
    }

    private static boolean SendRequest(Context ctx, String method, String route, JSONObject requestData, AbstractAPIResponseHandler handler)
    {
        String apiGatewayUrl = "http://" + GlobalVars.GetApiGatewayUrl() + route;

        DefaultResponseListener responseListener = new DefaultResponseListener(handler);

        int methodEnum = StringMethodToEnum(method);
        JsonObjectRequest request = new JsonObjectRequest(methodEnum, apiGatewayUrl, requestData, responseListener, responseListener);

        NetworkingSingleton.getInstance(ctx).addToRequestQueue(request);

        return true;
    }

    public static boolean GetAccessToken(Context ctx, String email, String password, AbstractAPIResponseHandler handler) {
        JSONObject requestData = new JSONObject();

        try {
            requestData.put("username", email);
            requestData.put("password", password);
            requestData.put("grant_type", "password");
            requestData.put("client_id", GlobalVars.GetApiClientId());
            requestData.put("client_secret", GlobalVars.GetApiClientSecret());
        } catch (JSONException e) {
            return false;
        }

        return SendRequest(ctx, "POST", "/oauth/token", requestData, handler);
    }

    public static boolean RefreshAccessToken(Context ctx, String refresh_token, AbstractAPIResponseHandler handler)
    {
        JSONObject requestData = new JSONObject();

        try {
            requestData.put("refresh_token", refresh_token);
            requestData.put("grant_type", "refresh_token");
            requestData.put("client_id", GlobalVars.GetApiClientId());
            requestData.put("client_secret", GlobalVars.GetApiClientSecret());
        } catch (JSONException e) {
            return false;
        }

        return SendRequest(ctx, "POST", "/oauth/token", requestData, handler);
    }

    public static void SaveAuthorizationParams(Context ctx, String access_token, String refresh_token, long expires_in)
    {
        // save authorization params
        SharedPrefSingleton.getInstance(ctx).SetValue(
                "string",
                ctx.getString(R.string.API_ACCESS_TOKEN),
                access_token
        );

        SharedPrefSingleton.getInstance(ctx).SetValue(
                "string",
                ctx.getString(R.string.API_REFRESH_TOKEN),
                refresh_token
        );

        SharedPrefSingleton.getInstance(ctx).SetValue(
                "long",
                ctx.getString(R.string.API_EXPIRES_AT),
                System.currentTimeMillis() / 1000L + expires_in
        );
    }
}
