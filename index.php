<?php
if(isset($_GET['token'])){

  include 'flappy/config.php';

  include '../../autoload.php';

  function token($tkn){
    try{
      $token = (new Mobius\Client\Auth\JWT(JWT_SECRET))->decode($tkn);
    }
    catch(Exception $e){
      $token = null;
    }
    return $token;
  }

  function app(){
    $token = token($_GET['token']);
    if($token){
      return new Mobius\Client\App(SECRET_KEY, $token->public_key);
    }
    return false;
  }

  // User has opened application page without a token
  $app = app();
  if(!$app){
    die("Visit https://store.mobius.network to register in the DApp Store");
  }

  // User has not granted access to his MOBI account so we can't use it for payments
  if(!$app->authorized()){
    die("Visit https://store.mobius.network and open our app");
  }

  header("Location: http://localhost/mobius/flappy?token=".$_GET['token']);
  die;
}

?>
<html>
  <head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.1/semantic.min.css">
    <style>
      .ui.container {
        margin: 2em 0em;
      }

      .ui.container > h1 {
        font-size: 3em;
      }

      .ui.container > h2.dividing.header {
        font-size: 2em;
        font-weight: normal;
        margin: 2em 0em 1em;
      }
    </style>
  </head>
  <body>
    <div class="ui container">
      <h1>Mobius Wallet App Dev Auth</h1>

      <h2 class="ui dividing header">Application</h2>

      <form class="ui form">
        <div class="field">
          <label>Auth endpoint:</label>
          <input type="text" value="http://localhost:3000/auth" id="url"></input>
        </div>
        <div class="field">
          <label>Redirect URI:</label>
          <input type="text" value="http://localhost:3000" id="redirect_url"></input>
        </div>
        <div class="field">
          <label>Public Key:</label>
          <input type="text" value="GCYEUO2EKQG3EQ7WVI3YSN7DUTMYLOMX4ZQO2AEVD2HA45IDN3JP4R3G"></input>
        </div>
        <div class="field">
          <label>Private Key:</label>
          <input type="text" value="SDGHOTL4QLVXNT6JKG444AY2YWCBBAZW3INXH3RBPPO2ZMFE7GRBAKFX"></input>
        </div>
      </form>

      

      <h2 class="ui dividing header">Normal Account</h2>

      <form class="ui form">
        <div class="field">
          <input type="text" value="GDGKFGQHB654TXNGE7AOSDL3LJUKDOTBXFWKN42AJVKLFUX2BC27INFB" />
        </div>
        <div class="field">
          <input type="text" value="SCQG6NTEFTOWWXO2IR7ICA4OZJACJJNXPRDXR7NIJRBCX5JQBXFJ2YTB" class="seed" />
        </div>
        <div class="field">
          <input type="submit" class="ui button green" value="Open" />
        </div>
      </form>

      

      <h2 class="ui dividing header">Zero Balance Account</h2>

      <form class="ui form">
        <div class="field">
          <input type="text" value="GAKFLHYINTXRZ5SWRCCVMKVE5L3O2VML35WHUVTZABRMZMPOWF35FFS5" />
        </div>
        <div class="field">
          <input type="text" value="SCFEJ7K4S25D77MJI2NTA3G4ZTSVQWX6JFGOZ4QHKP24DBRWG6GRRADU" class="seed" />
        </div>
        <div class="field">
          <input type="submit" class="ui button green" value="Open" />
        </div>
      </form>

      

      <h2 class="ui dividing header">Unauthorized Account</h2>

      <form class="ui form">
        <div class="field">
          <input type="text" value="GBNXIDRKVQ7XFQIN5RW5OREEKHGXI4VRWZUTFKDFNWP2KJFC2C6VPB6Y" />
        </div>
        <div class="field">
          <input type="text" value="SBOPXYR7KAPUFKCMCQVZNSZ24ZABAHW6HXAXXUJGLZPW4P3HI2VMKNRS" class="seed" />
        </div>
        <div class="field">
          <input type="submit" class="ui button green" value="Open" />
        </div>
      </form>

      
    </div>
  </body>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/stellar-sdk/0.8.0/stellar-sdk.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.3.1/semantic.min.js"></script>
  <script>
    StellarSdk.Network.useTestNetwork()

    $(function() {
      $(".ui.button").on('click', function(e) {
        e.preventDefault();
        var seed = $(e.target).closest('.ui.form').find('.seed:first').val();
        var keypair = StellarSdk.Keypair.fromSecret(seed);
        var endpoint_ruby = 'http://localhost:3000/auth';
        var endpoint = 'http://localhost/mobius/auth.php';

        var showError = function(err) {
          if (err) {
            alert(err);
          }
        }

        // NOTE: this should be replaced with mobius js sdk calls
        axios.get(endpoint).then(function(response) {
          var xdr = response.data;
          var tx = new StellarSdk.Transaction(xdr);
          tx.sign(keypair);
          var signedChallenge = tx.toEnvelope().toXDR("base64");
          axios({
            url: endpoint,
            method: 'post',
            params: {
              xdr: signedChallenge,
              public_key: keypair.publicKey()
            }
          }).then(function(response) {
            var url = $('#redirect_url').val();
            document.location = 'http://localhost/mobius' + '?token=' + response.data;
          }).catch(showError);
        }).catch(showError);
      });
    });
  </script>
</html>
