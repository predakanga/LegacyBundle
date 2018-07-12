# LegacyBundle
## WARNING: Proof-of-concept only
Provides helpers for wrapping legacy code in a Symfony application

This bundle allows for the usage of traditional URLs (i.e. "/forums.php?action=compose") within a Symfony application.
This is achieved by decorating the Symfony router, as the underlying structures don't seem to support this usecase.

To-do list:
- Revise the LegacyRoute annotation API
- Explore utilizing the base Symfony router by compiling LegacyRoutes to an expression on a standard route
