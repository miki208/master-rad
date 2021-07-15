package rs.miki208.myrunningbuddy.helpers;

import android.content.Context;
import android.widget.Toast;

import org.json.JSONException;
import org.json.JSONObject;

import java.lang.reflect.Method;
import java.net.HttpURLConnection;

import rs.miki208.myrunningbuddy.R;

public class APIObjectLoader {
    public interface APIObjectListener
    {
        void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, boolean authorizationErrors);
    }

    public static class PaginationInfo
    {
        int pageNumber;
        int itemsPerPage;

        public PaginationInfo()
        {
            pageNumber = -1;
            itemsPerPage = -1;
        }

        public PaginationInfo(int pageNumber, int itemsPerPage)
        {
            this.pageNumber = pageNumber;
            this.itemsPerPage = itemsPerPage;
        }

        public boolean IsValid()
        {
            if(pageNumber < 0 || itemsPerPage < 0)
                return false;

            return true;
        }
    }

    public static void LoadData(Context ctx, String className, String objectId, boolean useCache, long expiresIn, PaginationInfo paginationInfo, APIObjectListener loader)
    {
        if(className == null || className.isEmpty() || objectId == null || objectId.isEmpty())
        {
            loader.OnObjectLoaded(null, false);

            return;
        }

        if(useCache) {
            APIObjectCacheSingleton.CacheKey cacheKey = new APIObjectCacheSingleton.CacheKey(className, objectId);
            APIObjectCacheSingleton.CacheEntry object = APIObjectCacheSingleton.getInstance().GetObject(cacheKey);

            if(object != null)
            {
                loader.OnObjectLoaded(object, false);

                return;
            }
        }

        try {
            String methodName = Character.toUpperCase(className.charAt(0)) + className.substring(1);

            Method method = APIWrapper.class.getMethod("Get" + methodName, Context.class, String.class, AbstractAPIResponseHandler.class);

            method.invoke(null, ctx, objectId, new AbstractAPIResponseHandler() {
                @Override
                public void Handle(JSONObject response, int statusCode) throws JSONException {
                    switch(statusCode)
                    {
                        case HttpURLConnection.HTTP_OK:
                            APIObjectCacheSingleton.CacheKey cacheKey = new APIObjectCacheSingleton.CacheKey(className, objectId);
                            APIObjectCacheSingleton.CacheEntry cacheEntry = new APIObjectCacheSingleton.CacheEntry(response, APIObjectCacheSingleton.EntryType.JSONOBJECT);

                            APIObjectCacheSingleton.getInstance().AddObject(cacheKey, cacheEntry, expiresIn);

                            loader.OnObjectLoaded(cacheEntry, false);
                            break;
                        case HttpURLConnection.HTTP_UNAUTHORIZED:
                            Toast.makeText(ctx, APIWrapper.GetErrorMessageFromResponse(ctx, response, statusCode), Toast.LENGTH_LONG).show();

                            loader.OnObjectLoaded(null, true);
                            break;
                        default:
                            Toast.makeText(ctx, APIWrapper.GetErrorMessageFromResponse(ctx, response, statusCode), Toast.LENGTH_LONG).show();
                    }
                }
            });
        } catch (Exception e) {
            Toast.makeText(ctx, ctx.getString(R.string.unexpected_error), Toast.LENGTH_LONG).show();
        }
    }
}
