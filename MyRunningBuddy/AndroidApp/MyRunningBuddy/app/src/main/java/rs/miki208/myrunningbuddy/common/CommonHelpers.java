package rs.miki208.myrunningbuddy.common;

public class CommonHelpers {
    // Gets the number of seconds since the UNIX epoch
    public static long GetCurrentTimestamp()
    {
        return System.currentTimeMillis() / 1000L;
    }
}
