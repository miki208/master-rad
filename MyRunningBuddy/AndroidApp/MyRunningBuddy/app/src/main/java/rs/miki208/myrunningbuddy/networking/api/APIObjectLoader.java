package rs.miki208.myrunningbuddy.networking.api;

import android.content.Context;
import android.widget.Toast;

import org.json.JSONException;
import org.json.JSONObject;

import java.lang.reflect.Method;
import java.net.HttpURLConnection;

import rs.miki208.myrunningbuddy.R;

/***
 * Used for loading API object representations + it incorporates caching
 */
public class APIObjectLoader {
    // If you're using APIObjectLoader, you'll have to inherit this class to define what happens
    // when an object representation is loaded
    public interface APIObjectListener
    {
        void OnObjectLoaded(APIObjectCacheSingleton.CacheEntry obj, ErrorType errorCode);
    }

    public enum ErrorType {
        NO_ERROR,
        AUTHORIZATION_FAILED,
        NOT_FOUND,
        UNKNOWN_ERROR,
        MALFORMED_REQUEST,
        PRECONDITION_FAILED
    }

    public static class PaginationInfo
    {
        int pageNumber;
        int itemsPerPage;
        String newerThan;
        String olderThan;

        public PaginationInfo()
        {
            pageNumber = -1;
            itemsPerPage = -1;
        }

        public PaginationInfo(int pageNumber, int itemsPerPage, String newerThan, String olderThan)
        {
            this.pageNumber = pageNumber;
            this.itemsPerPage = itemsPerPage;
            this.newerThan = newerThan;
            this.olderThan = olderThan;
        }

        public boolean IsValid()
        {
            if(pageNumber < 0 || itemsPerPage < 0)
                return false;

            return true;
        }
    }

    // this is the main method for loading object representations
    // it accepts an object class name, unique id of an object, caching and pagination options as well as APIObjectLoader
    public static void LoadData(Context ctx, String className, String objectId, boolean useCache, long expiresIn, PaginationInfo paginationInfo, APIObjectListener loader)
    {
        //--- this request isn't valid because class name of the object is not valid or unique id is not present
        if(className == null || className.isEmpty() || objectId == null || objectId.isEmpty())
        {
            loader.OnObjectLoaded(null, ErrorType.MALFORMED_REQUEST);

            return;
        }

        //--- if caching is enabled, check if the object is already stored within the cache
        if(useCache) {
            APIObjectCacheSingleton.CacheKey cacheKey = new APIObjectCacheSingleton.CacheKey(className, objectId);
            APIObjectCacheSingleton.CacheEntry object = APIObjectCacheSingleton.getInstance().GetObject(cacheKey);

            if(object != null)
            {
                loader.OnObjectLoaded(object, ErrorType.NO_ERROR);

                return;
            }
        }

        try {
            //--- use Java reflection to find appropriate API method wrapper
            String methodName = Character.toUpperCase(className.charAt(0)) + className.substring(1);

            Method method = null;
            if(paginationInfo == null)
                method = APIWrapper.class.getMethod("Get" + methodName, Context.class, String.class, AbstractAPIResponseHandler.class);
            else
                method = APIWrapper.class.getMethod("Get" + methodName, Context.class, String.class, PaginationInfo.class, AbstractAPIResponseHandler.class);

            AbstractAPIResponseHandler handler = new AbstractAPIResponseHandler() {
                @Override
                public void Handle(JSONObject response, int statusCode) throws JSONException {
                    switch(statusCode)
                    {
                        case HttpURLConnection.HTTP_OK:
                            APIObjectCacheSingleton.CacheKey cacheKey = new APIObjectCacheSingleton.CacheKey(className, objectId);
                            APIObjectCacheSingleton.CacheEntry cacheEntry = new APIObjectCacheSingleton.CacheEntry(response, APIObjectCacheSingleton.EntryType.JSONOBJECT);

                            APIObjectCacheSingleton.getInstance().AddObject(cacheKey, cacheEntry, expiresIn);

                            loader.OnObjectLoaded(cacheEntry, ErrorType.NO_ERROR);
                            break;
                        case HttpURLConnection.HTTP_UNAUTHORIZED:
                            Toast.makeText(ctx, APIWrapper.GetErrorMessageFromResponse(ctx, response, statusCode), Toast.LENGTH_LONG).show();

                            loader.OnObjectLoaded(null, ErrorType.AUTHORIZATION_FAILED);
                            break;
                        case HttpURLConnection.HTTP_NOT_FOUND:
                            loader.OnObjectLoaded(null, ErrorType.NOT_FOUND);
                            break;
                        case HttpURLConnection.HTTP_PRECON_FAILED:
                            loader.OnObjectLoaded(null, ErrorType.PRECONDITION_FAILED);
                            break;
                        default:
                            Toast.makeText(ctx, APIWrapper.GetErrorMessageFromResponse(ctx, response, statusCode), Toast.LENGTH_LONG).show();

                            loader.OnObjectLoaded(null, ErrorType.UNKNOWN_ERROR);
                            break;
                    }
                }
            };

            //--- invoke the API wrapper
            if(paginationInfo == null)
                method.invoke(null, ctx, objectId, handler);
            else
                method.invoke(null, ctx, objectId, paginationInfo, handler);
        } catch (Exception e) {
            Toast.makeText(ctx, ctx.getString(R.string.unexpected_error), Toast.LENGTH_LONG).show();

            loader.OnObjectLoaded(null, ErrorType.MALFORMED_REQUEST);
        }
    }
}
