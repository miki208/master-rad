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
import com.android.volley.toolbox.JsonRequest;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.UnsupportedEncodingException;
import java.net.HttpURLConnection;
import java.util.HashMap;
import java.util.Map;

import javax.net.ssl.HttpsURLConnection;

import rs.miki208.myrunningbuddy.R;

public class APIWrapper {
    // helpers
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
            try
            {
                if(error instanceof AuthFailureError)
                {
                    JSONObject response = new JSONObject();
                    response.put("message", "Unauthorized");

                    handler.Handle(response, HttpsURLConnection.HTTP_UNAUTHORIZED);
                }
                else
                {
                    JSONObject response;
                    int statusCode = -1;

                    if(error.networkResponse != null)
                        statusCode = error.networkResponse.statusCode;

                    if(error.networkResponse != null && error.networkResponse.data != null)
                        response = new JSONObject(new String(error.networkResponse.data, HttpHeaderParser.parseCharset(error.networkResponse.headers)));
                    else
                        response = null;

                    handler.Handle(response, statusCode);
                }
            }
            catch(Exception ignored)
            {

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

    private static boolean SendRequest(Context ctx, String method, String route, Map<String, String> headers, JSONObject requestData, AbstractAPIResponseHandler handler)
    {
        String apiGatewayUrl = "http://" + GlobalVars.GetApiGatewayUrl() + route;

        DefaultResponseListener responseListener = new DefaultResponseListener(handler);

        int methodEnum = StringMethodToEnum(method);

        JsonObjectRequest request = new JsonObjectRequest(methodEnum, apiGatewayUrl, requestData, responseListener, responseListener) {
            @Override
            public Map<String, String> getHeaders() throws AuthFailureError {
                if(headers != null)
                    return headers;

                return super.getHeaders();
            }
        };

        NetworkingSingleton.getInstance(ctx).addToRequestQueue(request);

        return true;
    }

    public static String GetErrorMessageFromResponse(Context ctx, JSONObject response, int statusCode)
    {
        if(response == null || !response.has("message"))
            return ctx.getString(R.string.unexpected_error);

        if(statusCode == HttpURLConnection.HTTP_UNAUTHORIZED)
            return ctx.getString(R.string.unauthorized);

        if(statusCode == HttpURLConnection.HTTP_INTERNAL_ERROR)
            return ctx.getString(R.string.internal_service_unavailable);

        try {
            String message = response.getString("message");

            return message;
        } catch (JSONException e) {
            return "";
        }
    }

    private static boolean SendAuthorizedRequest(Context ctx, String method, String route, JSONObject requestData, AbstractAPIResponseHandler handler)
    {
        try
        {
            RefreshAccessTokenIfNeeded(ctx, new AbstractAPIResponseHandler() {
                @Override
                public void Handle(JSONObject response, int statusCode) throws JSONException {
                    switch (statusCode)
                    {
                        case HttpURLConnection.HTTP_OK:
                            String accessToken = GetAccessToken(ctx);
                            if(accessToken == null)
                                handler.Handle(null, HttpsURLConnection.HTTP_UNAUTHORIZED);

                            Map<String, String> headers = new HashMap<>();
                            headers.put("Authorization", "Bearer " + accessToken);

                            SendRequest(ctx, method, route, headers, requestData, handler);
                            break;
                        case HttpURLConnection.HTTP_UNAUTHORIZED:
                            handler.Handle(null, statusCode);
                            break;
                        default:
                            Toast.makeText(ctx, ctx.getString(R.string.unexpected_error), Toast.LENGTH_LONG).show();
                            break;
                    }
                }
            });
        } catch (JSONException e) {
            Toast.makeText(ctx, ctx.getString(R.string.unexpected_error), Toast.LENGTH_LONG).show();
        }

        return true;
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
                CommonHelpers.GetCurrentTimestamp() + expires_in
        );
    }

    public static String GetAccessToken(Context ctx)
    {
        return (String) SharedPrefSingleton.getInstance(ctx)
                .GetValue("string", ctx.getString(R.string.API_ACCESS_TOKEN));
    }

    public static long GetExpiresAt(Context ctx)
    {
        return (Long) SharedPrefSingleton.getInstance(ctx)
                .GetValue("long", ctx.getString(R.string.API_EXPIRES_AT));
    }

    public static String GetRefreshToken(Context ctx)
    {
        return (String) SharedPrefSingleton.getInstance(ctx)
                .GetValue("string", ctx.getString(R.string.API_REFRESH_TOKEN));
    }

    private static void RefreshAccessTokenIfNeeded(Context ctx, AbstractAPIResponseHandler handler) throws JSONException {
        String accessToken = GetAccessToken(ctx);
        String refreshToken = GetRefreshToken(ctx);
        long expiresAt = GetExpiresAt(ctx);

        if(accessToken == null || refreshToken == null || expiresAt == Long.MAX_VALUE)
        {
            handler.Handle(null, HttpsURLConnection.HTTP_UNAUTHORIZED);

            return;
        }

        long now = CommonHelpers.GetCurrentTimestamp();

        if(now > expiresAt)
        {
            APIWrapper.RefreshAccessToken(ctx, refreshToken, new AbstractAPIResponseHandler() {
                @Override
                public void Handle(JSONObject response, int statusCode) throws JSONException {
                    switch(statusCode)
                    {
                        case HttpURLConnection.HTTP_OK:
                            String access_token = response.getString("access_token");
                            String refresh_token = response.getString("refresh_token");
                            long expires_in = response.getLong("expires_in");

                            APIWrapper.SaveAuthorizationParams(ctx, access_token, refresh_token, expires_in);

                            handler.Handle(null, statusCode);
                            break;
                        default:
                            handler.Handle(null, HttpsURLConnection.HTTP_UNAUTHORIZED);
                            break;
                    }
                }
            });
        }
        else
        {
            handler.Handle(null, HttpsURLConnection.HTTP_OK);
        }
    }

    // api calls
    public static boolean GetAccessToken(Context ctx, String email, String password, AbstractAPIResponseHandler handler) {
        Map<String, String> headers = new HashMap<>();

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

        return SendRequest(ctx, "POST", "/oauth/token", headers, requestData, handler);
    }

    public static boolean RefreshAccessToken(Context ctx, String refresh_token, AbstractAPIResponseHandler handler)
    {
        Map<String, String> headers = new HashMap<>();

        JSONObject requestData = new JSONObject();

        try {
            requestData.put("refresh_token", refresh_token);
            requestData.put("grant_type", "refresh_token");
            requestData.put("client_id", GlobalVars.GetApiClientId());
            requestData.put("client_secret", GlobalVars.GetApiClientSecret());
        } catch (JSONException e) {
            return false;
        }

        return SendRequest(ctx, "POST", "/oauth/token", headers, requestData, handler);
    }

    public static boolean RegisterUser(Context ctx, String email, String password, String name, String surname, String location, String aboutme, AbstractAPIResponseHandler handler)
    {
        Map<String, String> headers = new HashMap<>();

        JSONObject requestData = new JSONObject();

        try {
            requestData.put("email", email);
            requestData.put("password", password);
            requestData.put("name", name);
            requestData.put("surname", surname.equals("") ? JSONObject.NULL : surname);
            requestData.put("aboutme", aboutme.equals("") ? JSONObject.NULL : aboutme);
            requestData.put("location", location.equals("") ? JSONObject.NULL : location);
        } catch (JSONException e) {
            return false;
        }

        return SendRequest(ctx, "POST", "/users", headers, requestData, handler);
    }

    public static boolean GetUser(Context ctx, String userId, AbstractAPIResponseHandler handler)
    {
        return SendAuthorizedRequest(ctx, "GET", "/user/" + userId, null, handler);
    }
}
