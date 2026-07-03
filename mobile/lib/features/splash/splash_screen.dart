import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:video_player/video_player.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  static const _assetPath = 'src/splash.mp4';
  static const _maxSplashDuration = Duration(seconds: 8);

  VideoPlayerController? _controller;
  bool _initialized = false;
  bool _navigated = false;
  Timer? _fallbackTimer;

  @override
  void initState() {
    super.initState();
    _fallbackTimer = Timer(_maxSplashDuration, _goHome);
    _initVideo();
  }

  Future<void> _initVideo() async {
    final controller = VideoPlayerController.asset(_assetPath);
    _controller = controller;
    controller.addListener(_onPlaybackUpdate);

    try {
      await controller.initialize();
      if (!mounted) return;

      // Required for autoplay on mobile web / Chrome.
      await controller.setVolume(0);
      await controller.setLooping(false);

      setState(() => _initialized = true);
      await controller.play();
    } catch (error, stackTrace) {
      debugPrint('Splash video failed: $error');
      debugPrint('$stackTrace');
      _goHome();
    }
  }

  void _onPlaybackUpdate() {
    final controller = _controller;
    if (controller == null || _navigated || !controller.value.isInitialized) {
      return;
    }

    final value = controller.value;
    if (value.isCompleted) {
      _goHome();
      return;
    }

    final duration = value.duration;
    final position = value.position;
    if (duration > Duration.zero &&
        position >= duration - const Duration(milliseconds: 300)) {
      _goHome();
    }
  }

  void _goHome() {
    if (_navigated) return;
    _navigated = true;
    _fallbackTimer?.cancel();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      context.go('/');
    });
  }

  @override
  void dispose() {
    _fallbackTimer?.cancel();
    _controller?.removeListener(_onPlaybackUpdate);
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final controller = _controller;

    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        fit: StackFit.expand,
        children: [
          GestureDetector(
            behavior: HitTestBehavior.opaque,
            onTap: _goHome,
            child: ColoredBox(
              color: Colors.white,
              child: Center(
                child: _initialized && controller != null
                    ? LayoutBuilder(
                        builder: (context, constraints) {
                          final size = controller.value.size;
                          final videoWidth = size.width > 0
                              ? size.width
                              : constraints.maxWidth;
                          final videoHeight = size.height > 0
                              ? size.height
                              : constraints.maxHeight;

                          return FittedBox(
                            fit: BoxFit.contain,
                            child: SizedBox(
                              width: videoWidth,
                              height: videoHeight,
                              child: VideoPlayer(controller),
                            ),
                          );
                        },
                      )
                    : const SizedBox.shrink(),
              ),
            ),
          ),
          Positioned(
            left: 16,
            bottom: 24,
            child: TextButton(
              onPressed: _goHome,
              child: const Text('تخطي'),
            ),
          ),
        ],
      ),
    );
  }
}
