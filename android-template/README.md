This is a template project for Android Studio that allows you to create an android webview application in minutes. You can use it to create a simple app for your website or as a starting point for your HTML5 based android app.

### Getting started

[Download](https://github.com/azhinu/Web-to-App/archive/master.zip) or clone this repository and import it into Android Studio.

### Using a remote source

1. Open `app/src/main/res/values/strings.xml` and edit `app_name` and `web_url` strings.

2. Open the `app/scr/main/java/com.example.app/MyWebViewClient.java` file and replace `example.com` on line **16** with your hostname.

	```java
	String hostname = "example.com";
	```
3. If you want to change package name, go to `app/build.gradle` and change it on line **7**./

Example:

```java
	applicationId "com.example.app"
```

### Using a local source

If you want to create a local HTML5 android app put all your files (including your `index.html`) in the `assets` directory

### Branding

If you want to change package name, go to `app/build.gradle` and change it on line **7**./

Example:

```java
	applicationId "com.example.app"
```

To change application icon, replace files in `app/src/main/res/mipmap-*` with your icon.
