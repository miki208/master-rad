package rs.miki208.myrunningbuddy.networking.api;

import android.content.Context;
import android.widget.Toast;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.HttpHeaderParser;
import com.android.volley.toolbox.JsonObjectRequest;

import org.json.JSONException;
import org.json.JSONObject;

import java.net.HttpURLConnection;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;

import javax.net.ssl.HttpsURLConnection;

import rs.miki208.myrunningbuddy.R;
import rs.miki208.myrunningbuddy.common.CommonHelpers;
import rs.miki208.myrunningbuddy.common.GlobalVars;
import rs.miki208.myrunningbuddy.common.SharedPrefSingleton;
import rs.miki208.myrunningbuddy.networking.NetworkingSingleton;

// Contains wrappers for My Running Buddy server side API procedures as well as helper methods and classes
public class APIWrapper {
    // Listener for volley regular and error responses which delegates the response to the AbstractAPIResponseHandler
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
                    //--- authentication errors should be treated as a regular response
                    JSONObject response = new JSONObject();
                    response.put("message", "Unauthorized");

                    handler.Handle(response, HttpsURLConnection.HTTP_UNAUTHORIZED);
                }
                else
                {
                    // extract as much info as possible from the error response (status code + response)
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

    // Sends an async HTTP request with given method, route and params
    private static boolean SendRequest(Context ctx, String method, String route, Map<String, String> headers, JSONObject requestData, AbstractAPIResponseHandler handler)
    {
        DefaultResponseListener responseListener = new DefaultResponseListener(handler);

        int methodEnum = StringMethodToEnum(method);

        //--- Volley doesn't support sending GET params as JSONObject
        //--- We'll append params to the url
        if(requestData != null && methodEnum == Request.Method.GET)
        {
            boolean first = true;

            Iterator<String> keys = requestData.keys();

            while(keys.hasNext())
            {
                String key = keys.next();

                if(first)
                {
                    first = false;
                    route += "?";
                }
                else
                {
                    route += "&";
                }

                try {
                    route += key + "=" + requestData.get(key);
                } catch (JSONException ignored) {

                }
            }

            requestData = null;
        }

        String apiGatewayUrl = "http://" + GlobalVars.GetApiGatewayUrl() + route;

        //--- add headers to the request
        JsonObjectRequest request = new JsonObjectRequest(methodEnum, apiGatewayUrl, requestData, responseListener, responseListener) {
            @Override
            public Map<String, String> getHeaders() throws AuthFailureError {
                if(headers != null)
                    return headers;

                return super.getHeaders();
            }
        };

        //--- add the request to the volley request queue
        NetworkingSingleton.getInstance(ctx).addToRequestQueue(request);

        return true;
    }

    // Helper for getting the human readable error message based on response and status code
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

    // Sends async HTTP request, but with authorization params, and refreshes tokens if needed
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
                            if(accessToken == null) // if token is not available, propagate an error message
                                handler.Handle(null, HttpsURLConnection.HTTP_UNAUTHORIZED);

                            //--- token was already valid, or a new token is generated successfully
                            Map<String, String> headers = new HashMap<>();

                            //--- add authentication token to the request header
                            headers.put("Authorization", "Bearer " + accessToken);

                            //--- send a regular async HTTP request
                            SendRequest(ctx, method, route, headers, requestData, handler);
                            break;
                        case HttpURLConnection.HTTP_UNAUTHORIZED:
                            //--- token is not generated successfully
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

    // Saves authorization params
    public static void SaveAuthorizationParams(Context ctx, String access_token, String refresh_token, long expires_in)
    {
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

    // Helpers for getting the authentication params
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

    // Checks whether access tokens is valid, and refreshes it if it's not
    private static void RefreshAccessTokenIfNeeded(Context ctx, AbstractAPIResponseHandler handler) throws JSONException {
        String accessToken = GetAccessToken(ctx);
        String refreshToken = GetRefreshToken(ctx);
        long expiresAt = GetExpiresAt(ctx);

        //--- we can't refresh the access token if a previous value of access token or refresh token is missing
        if(accessToken == null || refreshToken == null || expiresAt == Long.MAX_VALUE)
        {
            handler.Handle(null, HttpsURLConnection.HTTP_UNAUTHORIZED);

            return;
        }

        long now = CommonHelpers.GetCurrentTimestamp();

        //--- if the current access token expired, send a renewal request
        if(now > expiresAt)
        {
            APIWrapper.RefreshAccessToken(ctx, refreshToken, new AbstractAPIResponseHandler() {
                @Override
                public void Handle(JSONObject response, int statusCode) throws JSONException {
                    switch(statusCode)
                    {
                        case HttpURLConnection.HTTP_OK:
                            //--- ok, everything went well, extract auth info and save it for later
                            String access_token = response.getString("access_token");
                            String refresh_token = response.getString("refresh_token");
                            long expires_in = response.getLong("expires_in");

                            APIWrapper.SaveAuthorizationParams(ctx, access_token, refresh_token, expires_in);

                            handler.Handle(null, statusCode);
                            break;
                        default:
                            //--- unfortunately, renewal failed
                            handler.Handle(null, HttpsURLConnection.HTTP_UNAUTHORIZED);
                            break;
                    }
                }
            });
        }
        else
        {
            // use the current access token
            handler.Handle(null, HttpsURLConnection.HTTP_OK);
        }
    }

    /***
     * Wrappers for API procedures
     */
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
        email = email.trim();
        name = name.trim();
        surname = surname.trim();
        location = location.trim();
        aboutme = aboutme.trim();

        if(name.equals("") || email.equals(""))
        {
            Toast.makeText(ctx, ctx.getString(R.string.didnt_fill_required_fields), Toast.LENGTH_LONG).show();

            return false;
        }

        Map<String, String> headers = new HashMap<>();

        JSONObject requestData = new JSONObject();

        try {
            requestData.put("email", email);
            requestData.put("password", password);
            requestData.put("name", name);

            if(!surname.equals(""))
                requestData.put("surname", surname);

            if(!aboutme.equals(""))
                requestData.put("aboutme", aboutme);

            if(!location.equals(""))
                requestData.put("location", location);
        } catch (JSONException e) {
            return false;
        }

        return SendRequest(ctx, "POST", "/users", headers, requestData, handler);
    }

    public static boolean GetUser(Context ctx, String userId, AbstractAPIResponseHandler handler)
    {
        return SendAuthorizedRequest(ctx, "GET", "/user/" + userId, null, handler);
    }

    public static boolean GetNextMatch(Context ctx, String userId, AbstractAPIResponseHandler handler)
    {
        String priority_field = (String) SharedPrefSingleton.getInstance(ctx).GetValue("string", "priority_field");

        JSONObject requestData = new JSONObject();
        if(priority_field != null) {
            try {
                requestData.put("priority_field", priority_field);
            } catch (JSONException ignored) {

            }
        }

        return SendAuthorizedRequest(ctx, "GET", "/user/" + userId + "/next_match", requestData, handler);
    }

    public static boolean PostMatchAction(Context ctx, String userId, boolean accepted, AbstractAPIResponseHandler handler)
    {
        JSONObject requestData = new JSONObject();
        try {
            if (accepted)
                requestData.put("action", "accept");
            else
                requestData.put("action", "reject");
        }
        catch (JSONException ignored) {

        }

        return SendAuthorizedRequest(ctx, "POST", "/matcher/match/me/" + userId, requestData, handler);
    }

    public static boolean GetAuthorizationParams(Context ctx, String serviceName, AbstractAPIResponseHandler handler)
    {
        JSONObject requestData = new JSONObject();

        try {
            requestData.put("service_name", serviceName);

            return SendAuthorizedRequest(ctx, "GET", "/user/me/external_service_authorization_params", requestData, handler);
        } catch (JSONException e) {
            return false;
        }
    }

    public static boolean RevokeAuthorizationToExternalService(Context ctx, String serviceName, AbstractAPIResponseHandler handler)
    {
        return SendAuthorizedRequest(ctx, "DELETE", "/user/me/external_service/" + serviceName, null, handler);
    }

    public static boolean UpdateUser(Context ctx, String surname, String location, String aboutme, AbstractAPIResponseHandler handler)
    {
        JSONObject requestData = new JSONObject();

        try {
            requestData.put("surname", surname);
            requestData.put("location", location);
            requestData.put("aboutme", aboutme);

            return SendAuthorizedRequest(ctx, "PATCH", "/user/me", requestData, handler);
        } catch (JSONException e) {
            return false;
        }
    }

    public static boolean GetConversations(Context ctx, String userId, APIObjectLoader.PaginationInfo paginationInfo, AbstractAPIResponseHandler handler)
    {
        JSONObject requestData = new JSONObject();

        try {
            requestData.put("page", paginationInfo.pageNumber);
            requestData.put("num_of_results_per_page", paginationInfo.itemsPerPage);

            if(paginationInfo.newerThan != null)
                requestData.put("conversations_newer_than", paginationInfo.newerThan);

            if(paginationInfo.olderThan != null)
                requestData.put("conversations_older_than", paginationInfo.olderThan);

            return SendAuthorizedRequest(ctx, "GET", "/user/" + userId + "/messages", requestData, handler);
        } catch (JSONException e)
        {
            return false;
        }
    }

    public static boolean GetMessages(Context ctx, String userId, APIObjectLoader.PaginationInfo paginationInfo, AbstractAPIResponseHandler handler)
    {
        JSONObject requestData = new JSONObject();

        try {
            requestData.put("page", paginationInfo.pageNumber);
            requestData.put("num_of_results_per_page", paginationInfo.itemsPerPage);

            if(paginationInfo.newerThan != null)
                requestData.put("messages_newer_than", paginationInfo.newerThan);

            if(paginationInfo.olderThan != null)
                requestData.put("messages_older_than", paginationInfo.olderThan);

            return SendAuthorizedRequest(ctx, "GET", "/user/me/messages/" + userId, requestData, handler);
        } catch (JSONException e)
        {
            return false;
        }
    }

    public static boolean SendMessage(Context ctx, String userId, String message, AbstractAPIResponseHandler handler)
    {
        JSONObject requestData = new JSONObject();

        try {
            requestData.put("message", message);
        } catch (JSONException e) {
            return false;
        }

        return SendAuthorizedRequest(ctx, "POST", "/user/me/messages/" + userId, requestData, handler);
    }

    public static boolean RevokeAccessToken(Context ctx, AbstractAPIResponseHandler handler)
    {
        return SendAuthorizedRequest(ctx, "DELETE", "/oauth/token", null, handler);
    }
}
