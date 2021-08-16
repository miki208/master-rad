package rs.miki208.myrunningbuddy.helpers;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;

public class APIObjectCacheSingleton {
    public enum EntryType {
        JSONOBJECT,
        JSONARRAY,
        IMAGE
    }

    public static class CacheEntry {
        public static final long withoutExpiration = Long.MAX_VALUE;

        public Object cachedObject;
        public EntryType objectType;
        public long expiresAt;

        public CacheEntry(Object cachedObject, EntryType objectType)
        {
            expiresAt = 0;

            this.cachedObject = cachedObject;
            this.objectType = objectType;
        }
    }

    public static class CacheKey
    {
        public String className;
        public String objectId;

        public CacheKey(String className, String objectId)
        {
            this.className = className;
            this.objectId = objectId;
        }

        @Override
        public boolean equals(Object obj)
        {
            if(this == obj)
                return true;

            if(obj == null || obj.getClass() != this.getClass())
                return false;

            CacheKey otherCacheKey = (CacheKey) obj;

            return this.className.equals(otherCacheKey.className) && this.objectId.equals(otherCacheKey.objectId);
        }

        @Override
        public int hashCode() {
            return 1;
        }
    }

    private APIObjectCacheSingleton()
    {
        numberOfInserts = 0;
    }

    public static synchronized APIObjectCacheSingleton getInstance() {
        if (instance == null)
            instance = new APIObjectCacheSingleton();

        return instance;
    }

    public CacheEntry GetObject(CacheKey key)
    {
        long now = CommonHelpers.GetCurrentTimestamp();

        CacheEntry entry = objectCacheMap.get(key);

        if(entry == null || entry.expiresAt < now)
            return null;

        return entry;
    }

    public JSONArray GetObjectAsJsonArray(CacheKey key)
    {
        Object obj = GetObject(key);

        if(obj != null && ((CacheEntry) obj).objectType == EntryType.JSONARRAY)
            return (JSONArray) ((CacheEntry) obj).cachedObject;
        else
            return null;
    }

    public JSONObject GetObjectAsJsonObject(CacheKey key)
    {
        Object obj = GetObject(key);

        if(obj != null && ((CacheEntry) obj).objectType == EntryType.JSONOBJECT)
            return (JSONObject) ((CacheEntry) obj).cachedObject;
        else
            return null;
    }

    public void AddObject(CacheKey key, CacheEntry entry, long expiresIn)
    {
        numberOfInserts++;
        long now = CommonHelpers.GetCurrentTimestamp();

        long expiresAt = 0;
        if(expiresIn != CacheEntry.withoutExpiration)
            expiresAt = now + expiresIn;
        else
            expiresAt = CacheEntry.withoutExpiration;

        entry.expiresAt = expiresAt;

        if(numberOfInserts == 100)
        {
            //clear expired items
            for(Iterator<Map.Entry<CacheKey, CacheEntry>> it = objectCacheMap.entrySet().iterator(); it.hasNext();)
            {
                Map.Entry<CacheKey, CacheEntry> elem = it.next();
                if(elem.getValue().expiresAt < now)
                    it.remove();
            }

            numberOfInserts = 0;
        }

        objectCacheMap.put(key, entry);
    }

    public void RemoveAll()
    {
        numberOfInserts = 0;

        objectCacheMap.clear();
    }

    private static APIObjectCacheSingleton instance;

    HashMap<CacheKey, CacheEntry> objectCacheMap = new HashMap<>();
    int numberOfInserts;
}
