export class oauth2
  {
    constructor()
      {
        this.token=null;
        this.location="https://oauth2.sexycoders.org";
        this.get_token=this.location+"/token.php";
        this.validate=this.location+"/validate.php";
      }
  }

export function unlock_oauth2()
  {
    var data=new Object();
    data.username=($('form').serializeArray()[0].value);
    data.password=($('form').serializeArray()[1].value);
    data.grant_type='password';
    data.command='token';
    if(window.__auth__==undefined)
      window.__auth__=new AuthObject('oauth2');
    $.ajax({
        type: 'POST',
        url: window.__auth__.get_token,
        headers: {"Access-Control-Allow-Origin":"https://uniclient.sexycoders.org/"},
        data: "grant_type=client_credentials&client_id="+data.username+
            "&client_secret="+data.password,
        success:
        function(response)
            {
                console.log(response);
                var data=response;
                if(data.access_token!=undefined)
                  {
                    window.__auth_flag=1;
                    window.__auth__.oauth2.token=data.access_token;
                    localStorage.setItem("oauth2_token",data.access_token);
                  }
            },
        async:false
        });
  }
