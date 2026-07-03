import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/config/api_base_url.dart';
import '../../core/config/api_base_url_provider.dart';
import '../../core/config/env.dart';

Future<void> showServerSettingsSheet(
  BuildContext context,
  WidgetRef ref, {
  VoidCallback? onSaved,
}) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    builder: (context) => Padding(
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: MediaQuery.viewInsetsOf(context).bottom + 24,
      ),
      child: _ServerSettingsForm(
        onSaved: () {
          Navigator.pop(context);
          onSaved?.call();
        },
      ),
    ),
  );
}

class _ServerSettingsForm extends ConsumerStatefulWidget {
  const _ServerSettingsForm({this.onSaved});

  final VoidCallback? onSaved;

  @override
  ConsumerState<_ServerSettingsForm> createState() =>
      _ServerSettingsFormState();
}

class _ServerSettingsFormState extends ConsumerState<_ServerSettingsForm> {
  late final TextEditingController _controller;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: ApiBaseUrl.current);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final url = _controller.text.trim();
    if (url.isEmpty) return;

    setState(() => _saving = true);
    try {
      await updateApiBaseUrl(ref, url);
      widget.onSaved?.call();
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _reset() async {
    setState(() => _saving = true);
    try {
      await resetApiBaseUrl(ref);
      _controller.text = Env.defaultApiBaseUrl;
      widget.onSaved?.call();
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text(
          'إعدادات الاتصال بالخادم',
          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 8),
        const Text(
          'على الهاتف الحقيقي استخدم IP جهاز الكمبيوتر على الشبكة المحلية، '
          'مثال: http://192.168.1.10:8000/api/v1',
          style: TextStyle(fontSize: 13),
        ),
        const SizedBox(height: 16),
        TextField(
          controller: _controller,
          decoration: const InputDecoration(
            labelText: 'عنوان API',
            hintText: 'http://192.168.1.10:8000/api/v1',
          ),
          keyboardType: TextInputType.url,
          textDirection: TextDirection.ltr,
          autocorrect: false,
        ),
        const SizedBox(height: 8),
        Text(
          'الافتراضي: ${Env.defaultApiBaseUrl}',
          style: const TextStyle(fontSize: 12),
          textDirection: TextDirection.ltr,
        ),
        const SizedBox(height: 16),
        ElevatedButton(
          onPressed: _saving ? null : _save,
          child: _saving
              ? const SizedBox(
                  height: 20,
                  width: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Text('حفظ وإعادة المحاولة'),
        ),
        TextButton(
          onPressed: _saving ? null : _reset,
          child: const Text('استعادة الافتراضي'),
        ),
      ],
    );
  }
}
