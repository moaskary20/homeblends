import 'package:intl/intl.dart';

final _formatter = NumberFormat.currency(
  locale: 'ar_EG',
  symbol: 'ج.م',
  decimalDigits: 2,
);

String formatPrice(num? value) {
  if (value == null) return '—';
  return _formatter.format(value);
}
