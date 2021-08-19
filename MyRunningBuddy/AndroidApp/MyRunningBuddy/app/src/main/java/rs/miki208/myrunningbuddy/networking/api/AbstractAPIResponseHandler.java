package rs.miki208.myrunningbuddy.networking.api;

import org.json.JSONException;
import org.json.JSONObject;

// Handler for async HTTP requests
public abstract class AbstractAPIResponseHandler {
    public abstract void Handle(JSONObject response, int statusCode) throws JSONException;
}
