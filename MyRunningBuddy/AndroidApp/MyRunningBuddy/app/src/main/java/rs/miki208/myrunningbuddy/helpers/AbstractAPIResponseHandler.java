package rs.miki208.myrunningbuddy.helpers;

import org.json.JSONException;
import org.json.JSONObject;

public abstract class AbstractAPIResponseHandler {
    public abstract void Handle(JSONObject response, int statusCode) throws JSONException;
}
