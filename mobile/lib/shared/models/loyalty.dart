class VipLevel {
  const VipLevel({
    required this.id,
    required this.name,
    this.discountPercent = 0,
  });

  factory VipLevel.fromJson(Map<String, dynamic> json) => VipLevel(
        id: json['id'] as int,
        name: json['name'] as String,
        discountPercent: _toNum(json['discount_percent']) ?? 0,
      );

  final int id;
  final String name;
  final num discountPercent;
}

class LoyaltyProgram {
  const LoyaltyProgram({
    required this.points,
    required this.storeCredit,
    required this.maxWalletRedeemPoints,
    required this.pointValue,
    required this.earnPerCurrency,
    required this.earnRateLabel,
    required this.expiryMonths,
    required this.minRedeemPoints,
    required this.maxRedeemPercent,
    required this.vipDiscountPercent,
    this.vipLevel,
  });

  factory LoyaltyProgram.fromJson(Map<String, dynamic> json) => LoyaltyProgram(
        points: json['points'] as int? ?? 0,
        storeCredit: _toNum(json['store_credit']) ?? 0,
        maxWalletRedeemPoints: json['max_wallet_redeem_points'] as int? ?? 0,
        pointValue: _toNum(json['point_value']) ?? 0,
        earnPerCurrency: json['earn_per_currency'] as int? ?? 10,
        earnRateLabel: json['earn_rate_label'] as String? ?? '',
        expiryMonths: json['expiry_months'] as int? ?? 12,
        minRedeemPoints: json['min_redeem_points'] as int? ?? 10,
        maxRedeemPercent: json['max_redeem_percent'] as int? ?? 50,
        vipDiscountPercent: _toNum(json['vip_discount_percent']) ?? 0,
        vipLevel: json['vip_level'] is Map<String, dynamic>
            ? VipLevel.fromJson(json['vip_level'] as Map<String, dynamic>)
            : null,
      );

  final int points;
  final num storeCredit;
  final int maxWalletRedeemPoints;
  final num pointValue;
  final int earnPerCurrency;
  final String earnRateLabel;
  final int expiryMonths;
  final int minRedeemPoints;
  final int maxRedeemPercent;
  final num vipDiscountPercent;
  final VipLevel? vipLevel;

  num get pointsCashValue => points * pointValue;

  bool get canRedeemToWallet =>
      maxWalletRedeemPoints >= minRedeemPoints && minRedeemPoints > 0;
}

class LoyaltyTransaction {
  const LoyaltyTransaction({
    required this.id,
    required this.points,
    required this.type,
    required this.typeLabel,
    this.description,
    this.orderId,
    this.expiresAt,
    this.createdAt,
  });

  factory LoyaltyTransaction.fromJson(Map<String, dynamic> json) =>
      LoyaltyTransaction(
        id: json['id'] as int,
        points: json['points'] as int,
        type: json['type'] as String,
        typeLabel: json['type_label'] as String? ?? json['type'] as String,
        description: json['description'] as String?,
        orderId: json['order_id'] as int?,
        expiresAt: json['expires_at'] as String?,
        createdAt: json['created_at'] as String?,
      );

  final int id;
  final int points;
  final String type;
  final String typeLabel;
  final String? description;
  final int? orderId;
  final String? expiresAt;
  final String? createdAt;

  bool get isCredit => points >= 0;
}

num? _toNum(dynamic value) {
  if (value == null) return null;
  if (value is num) return value;
  return num.tryParse(value.toString());
}
