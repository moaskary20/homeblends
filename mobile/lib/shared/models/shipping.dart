class ShippingRate {
  const ShippingRate({
    required this.id,
    required this.name,
    required this.rate,
    this.type,
    this.estimatedDays,
    this.zone,
  });

  factory ShippingRate.fromJson(Map<String, dynamic> json) => ShippingRate(
        id: json['id'] as int,
        name: json['name'] as String,
        rate: _toNum(json['rate']) ?? 0,
        type: json['type'] as String?,
        estimatedDays: json['estimated_days'] as int?,
        zone: json['zone'] as String?,
      );

  final int id;
  final String name;
  final num rate;
  final String? type;
  final int? estimatedDays;
  final String? zone;
}

class PaymentGateway {
  const PaymentGateway({
    required this.code,
    required this.name,
    this.description,
    this.instructions,
    this.codFee = 0,
  });

  factory PaymentGateway.fromJson(Map<String, dynamic> json) => PaymentGateway(
        code: json['code'] as String,
        name: json['name'] as String,
        description: json['description'] as String?,
        instructions: json['instructions'] as String?,
        codFee: _toNum(json['cod_fee']) ?? 0,
      );

  final String code;
  final String name;
  final String? description;
  final String? instructions;
  final num codFee;
}

num? _toNum(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString());
}
