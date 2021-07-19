# PortalPM
A PocketMine-MP plugin implementation of the [Portal](https://github.com/Paroxity/portal) TCP socket API. Download from [poggit](https://poggit.pmmp.io/ci/Paroxity/PortalPM)

## Usage
1. Make sure you already have [Portal](https://github.com/Paroxity/portal) set up
2. Edit ``config.yml`` and update the credentials/information to match your Portal configuration
3. This plugin uses [Commando](https://github.com/Paroxity/Commando) virion for registering commands. Make sure to have that in case you are running from source.
4. Run the server and wait for the connection to authenticate.
   - If successful, you should see ``[Portal] Authentication was successful`` in console
   - If the connection failed to authenticate, you will see an error telling you what is wrong
5. The server is now connected to the proxy and can communicate using the API