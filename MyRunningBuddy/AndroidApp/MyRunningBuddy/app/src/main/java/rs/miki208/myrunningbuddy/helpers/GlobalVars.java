package rs.miki208.myrunningbuddy.helpers;

public class GlobalVars {
    private static String apiGatewayUrl = "192.168.0.109:8002";
    private static String apiClientSecret = "6KiTMMoG9c0nXKz5dwBIfothhSEmiQ5NdTxZMuyi";
    private static int apiClientId = 2;

    public static String GetApiGatewayUrl()
    {
        return apiGatewayUrl;
    }

    public static String GetApiClientSecret()
    {
        return apiClientSecret;
    }

    public static int GetApiClientId()
    {
        return apiClientId;
    }
}
