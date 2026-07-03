class User {
  const User({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.locale,
    this.currency,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json['id'] as int,
        name: json['name'] as String,
        email: json['email'] as String,
        phone: json['phone'] as String?,
        locale: json['locale'] as String?,
        currency: json['currency'] as String?,
      );

  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? locale;
  final String? currency;
}

class AuthResponse {
  const AuthResponse({required this.user, required this.token, this.message});

  factory AuthResponse.fromJson(Map<String, dynamic> json) => AuthResponse(
        user: User.fromJson(json['user'] as Map<String, dynamic>),
        token: json['token'] as String,
        message: json['message'] as String?,
      );

  final User user;
  final String token;
  final String? message;
}
