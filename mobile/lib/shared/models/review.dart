class Review {
  const Review({
    required this.id,
    required this.rating,
    this.comment,
    this.isVerifiedPurchase = false,
    this.userName,
    this.createdAt,
  });

  factory Review.fromJson(Map<String, dynamic> json) => Review(
        id: json['id'] as int,
        rating: json['rating'] as int,
        comment: json['comment'] as String?,
        isVerifiedPurchase: json['is_verified_purchase'] as bool? ?? false,
        userName: json['user_name'] as String?,
        createdAt: json['created_at']?.toString(),
      );

  final int id;
  final int rating;
  final String? comment;
  final bool isVerifiedPurchase;
  final String? userName;
  final String? createdAt;
}
