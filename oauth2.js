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
