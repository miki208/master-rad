package rs.miki208.myrunningbuddy.helpers;

public class GlobalVars {
    private static String apiGatewayUrl = "10.0.2.2:8002";
    private static String apiClientSecret = "6ssh1SG86uEewFhU5zSfqkx4Cd6d4lrBH3PsnjUb";
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
