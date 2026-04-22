import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_downloader/flutter_downloader.dart';
import 'package:path_provider/path_provider.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

class WebViewScreen extends StatefulWidget {
  const WebViewScreen({super.key});

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen> {
  final GlobalKey webViewKey = GlobalKey();
  InAppWebViewController? webViewController;
  
  // URL to load. If serving the local HTML, you can use:
  // URLRequest(url: WebUri("asset://assets/index.html"))
  // Assuming the user has a live website URL they want to load.
  final String targetUrl = "https://www.askvisa.com"; // Replace with actual URL
  
  bool isConnected = true;
  double progress = 0;
  bool isLoading = true;

  PullToRefreshController? pullToRefreshController;
  late StreamSubscription<List<ConnectivityResult>> connectivitySubscription;

  @override
  void initState() {
    super.initState();
    _checkInitialConnectivity();
    _setupConnectivityListener();
    _setupFCM();
    _requestPermissions();

    pullToRefreshController = PullToRefreshController(
      settings: PullToRefreshSettings(
        color: Colors.blueAccent,
      ),
      onRefresh: () async {
        if (Platform.isAndroid) {
          webViewController?.reload();
        } else if (Platform.isIOS) {
          final url = await webViewController?.getUrl();
          if (url != null) {
            webViewController?.loadUrl(urlRequest: URLRequest(url: url));
          }
        }
      },
    );
  }

  @override
  void dispose() {
    connectivitySubscription.cancel();
    super.dispose();
  }

  Future<void> _checkInitialConnectivity() async {
    final connectivityResult = await (Connectivity().checkConnectivity());
    if (connectivityResult.contains(ConnectivityResult.none)) {
      setState(() => isConnected = false);
    }
  }

  void _setupConnectivityListener() {
    connectivitySubscription = Connectivity().onConnectivityChanged.listen((List<ConnectivityResult> results) {
      bool connected = !results.contains(ConnectivityResult.none);
      if (connected != isConnected) {
        setState(() {
          isConnected = connected;
          if (isConnected && webViewController != null) {
            webViewController!.reload();
          }
        });
      }
    });
  }

  Future<void> _requestPermissions() async {
    await [
      Permission.storage,
      Permission.camera,
      Permission.notification,
    ].request();
  }

  void _setupFCM() {
    FirebaseMessaging.instance.getInitialMessage().then((RemoteMessage? message) {
      if (message != null && message.data.containsKey('url')) {
        _loadUrlFromNotification(message.data['url']);
      }
    });

    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      if (message.data.containsKey('url')) {
        _loadUrlFromNotification(message.data['url']);
      }
    });
  }

  void _loadUrlFromNotification(String url) {
    if (webViewController != null) {
      webViewController!.loadUrl(urlRequest: URLRequest(url: WebUri(url)));
    }
  }

  Future<bool> _onWillPop() async {
    if (webViewController != null) {
      if (await webViewController!.canGoBack()) {
        webViewController!.goBack();
        return false;
      }
    }
    return true; // Exit app
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: _onWillPop,
      child: Scaffold(
        backgroundColor: Colors.white,
        body: SafeArea(
          child: !isConnected
              ? _buildOfflineUI()
              : Stack(
                  children: [
                    InAppWebView(
                      key: webViewKey,
                      initialUrlRequest: URLRequest(url: WebUri(targetUrl)),
                      pullToRefreshController: pullToRefreshController,
                      initialSettings: InAppWebViewSettings(
                        javaScriptEnabled: true,
                        domStorageEnabled: true,
                        transparentBackground: true,
                        useShouldOverrideUrlLoading: true,
                        useOnDownloadStart: true,
                        allowFileAccessFromFileURLs: true,
                        allowUniversalAccessFromFileURLs: true,
                        hardwareAcceleration: true,
                        allowsInlineMediaPlayback: true,
                        supportZoom: false, // For native app feel
                        mixedContentMode: MixedContentMode.MIXED_CONTENT_ALWAYS_ALLOW,
                      ),
                      onWebViewCreated: (controller) {
                        webViewController = controller;
                      },
                      onLoadStart: (controller, url) {
                        setState(() {
                          isLoading = true;
                        });
                      },
                      shouldOverrideUrlLoading: (controller, navigationAction) async {
                        var uri = navigationAction.request.url;

                        if (!["http", "https", "file", "chrome", "data", "javascript", "about"].contains(uri.scheme)) {
                          // Open external apps (whatsapp, mailto, tel, etc.)
                          if (await canLaunchUrl(uri)) {
                            await launchUrl(uri, mode: LaunchMode.externalApplication);
                            return NavigationActionPolicy.CANCEL;
                          }
                        }
                        return NavigationActionPolicy.ALLOW;
                      },
                      onLoadStop: (controller, url) async {
                        pullToRefreshController?.endRefreshing();
                        setState(() {
                          isLoading = false;
                        });
                      },
                      onProgressChanged: (controller, progress) {
                        if (progress == 100) {
                          pullToRefreshController?.endRefreshing();
                        }
                        setState(() {
                          this.progress = progress / 100;
                        });
                      },
                      onDownloadStartRequest: (controller, downloadStartRequest) async {
                        // Request permission and handle file download
                        var status = await Permission.storage.request();
                        if (status.isGranted) {
                          Directory? externalDir;
                          if (Platform.isAndroid) {
                            externalDir = await getExternalStorageDirectory();
                          } else {
                            externalDir = await getApplicationDocumentsDirectory();
                          }

                          final savedDir = externalDir?.path;
                          if (savedDir != null) {
                            await FlutterDownloader.enqueue(
                              url: downloadStartRequest.url.toString(),
                              savedDir: savedDir,
                              fileName: downloadStartRequest.suggestedFilename,
                              showNotification: true,
                              openFileFromNotification: true,
                              saveInPublicStorage: true,
                            );
                            if (context.mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text("Download started...")),
                              );
                            }
                          }
                        } else {
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text("Storage permission denied. Cannot download.")),
                            );
                          }
                        }
                      },
                    ),
                    if (isLoading)
                      Positioned(
                        top: 0,
                        left: 0,
                        right: 0,
                        child: LinearProgressIndicator(
                          value: progress,
                          backgroundColor: Colors.transparent,
                          color: Colors.blueAccent,
                        ),
                      ),
                  ],
                ),
        ),
      ),
    );
  }

  Widget _buildOfflineUI() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.wifi_off, size: 80, color: Colors.grey),
          const SizedBox(height: 20),
          const Text(
            "No Internet Connection",
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 10),
          const Text("Please check your network and try again."),
          const SizedBox(height: 20),
          ElevatedButton(
            onPressed: () {
              _checkInitialConnectivity();
            },
            child: const Text("Retry"),
          ),
        ],
      ),
    );
  }
}
