package rs.miki208.myrunningbuddy.helpers;

public class GlobalVars {
    private static String apiGatewayUrl = "hwsrv-894054.hostwindsdns.com:8002";

    private static String apiClientSecret = "LPB4wCBB0hghqss5wWTvqaKOGMnJ9eJAP0hJzHK8";

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
