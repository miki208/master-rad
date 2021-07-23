package rs.miki208.myrunningbuddy.helpers;

import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.os.AsyncTask;
import android.util.Log;
import android.widget.ImageView;

import java.io.InputStream;

public class DownloadImageTask extends AsyncTask<String, Void, Bitmap> {
    ImageView imageView;
    Bitmap cacheImage;
    String imageUrl;

    public DownloadImageTask(ImageView imageView, String imageUrl) {
        this.imageView = imageView;
        this.cacheImage = null;
        this.imageUrl = imageUrl;

        APIObjectCacheSingleton.CacheKey key = new APIObjectCacheSingleton.CacheKey("img", imageUrl);
        APIObjectCacheSingleton.CacheEntry entry = APIObjectCacheSingleton.getInstance().GetObject(key);

        if(entry != null)
            cacheImage = (Bitmap) entry.cachedObject;
    }

    protected Bitmap doInBackground(String... ignore) {
        if(cacheImage != null)
            return cacheImage;

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

        if(cacheImage == null)
        {
            APIObjectCacheSingleton.CacheKey key = new APIObjectCacheSingleton.CacheKey("img", imageUrl);
            APIObjectCacheSingleton.CacheEntry entry = new APIObjectCacheSingleton.CacheEntry(result, APIObjectCacheSingleton.EntryType.IMAGE);

            APIObjectCacheSingleton.getInstance().AddObject(key, entry, 60 * 60);
        }
    }
}