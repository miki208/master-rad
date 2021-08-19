package rs.miki208.myrunningbuddy.networking;

import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.os.AsyncTask;
import android.widget.ImageView;

import java.io.InputStream;

import rs.miki208.myrunningbuddy.networking.api.APIObjectCacheSingleton;

public class DownloadImageTask extends AsyncTask<String, Void, Bitmap> {
    ImageView imageView;
    Bitmap cacheImage;
    String imageUrl;

    public DownloadImageTask(ImageView imageView, String imageUrl) {
        this.imageView = imageView;
        this.cacheImage = null;
        this.imageUrl = imageUrl;

        //--- check if image is already cached
        APIObjectCacheSingleton.CacheKey key = new APIObjectCacheSingleton.CacheKey("img", imageUrl);
        APIObjectCacheSingleton.CacheEntry entry = APIObjectCacheSingleton.getInstance().GetObject(key);

        if(entry != null)
            cacheImage = (Bitmap) entry.cachedObject;
    }

    protected Bitmap doInBackground(String... ignore) {
        //--- there is no need to download image again if it's already in the cache
        if(cacheImage != null)
            return cacheImage;

        //--- download image
        Bitmap img = null;

        try {
            InputStream in = new java.net.URL(imageUrl).openStream();
            img = BitmapFactory.decodeStream(in);
        } catch (Exception ignored) {

        }

        return img;
    }

    protected void onPostExecute(Bitmap result) {
        if(result != null)
            imageView.setImageBitmap(result);

        //--- put the newly downloaded image to the cache
        if(cacheImage == null)
        {
            APIObjectCacheSingleton.CacheKey key = new APIObjectCacheSingleton.CacheKey("img", imageUrl);
            APIObjectCacheSingleton.CacheEntry entry = new APIObjectCacheSingleton.CacheEntry(result, APIObjectCacheSingleton.EntryType.IMAGE);

            APIObjectCacheSingleton.getInstance().AddObject(key, entry, 60 * 60);
        }
    }
}