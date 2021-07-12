package rs.miki208.myrunningbuddy.helpers;

import android.content.Context;
import android.content.SharedPreferences;
import android.preference.PreferenceManager;

public class SharedPrefSingleton {
    private static SharedPrefSingleton instance;

    private final SharedPreferences sharedPref;

    private SharedPrefSingleton(Context context) {
        sharedPref = PreferenceManager.getDefaultSharedPreferences(context);
    }

    public static synchronized SharedPrefSingleton getInstance(Context context) {
        if (instance == null)
            instance = new SharedPrefSingleton(context);

        return instance;
    }

    public Object GetValue(String type, String key)
    {
        switch(type)
        {
            case "int":
                return sharedPref.getInt(key, Integer.MAX_VALUE);
            case "string":
                return sharedPref.getString(key, null);
            case "long":
                return sharedPref.getLong(key, Long.MAX_VALUE);
            default:
                return null;
        }
    }

    public void SetValue(String type, String key, Object value)
    {
        SharedPreferences.Editor editor = sharedPref.edit();

        switch (type)
        {
            case "int":
                editor.putInt(key, (Integer) value);
                break;
            case "string":
                editor.putString(key, (String) value);
                break;
            case "long":
                editor.putLong(key, (Long) value);
            default:
                break;
        }

        editor.apply();
    }
}
