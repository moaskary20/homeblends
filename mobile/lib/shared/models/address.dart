class Address {
  const Address({
    required this.id,
    required this.firstName,
    required this.lastName,
    required this.phone,
    required this.addressLine1,
    required this.city,
    required this.country,
    this.label,
    this.addressLine2,
    this.state,
    this.postalCode,
    this.isDefault = false,
    this.fullName,
  });

  factory Address.fromJson(Map<String, dynamic> json) => Address(
        id: json['id'] as int,
        label: json['label'] as String?,
        firstName: json['first_name'] as String,
        lastName: json['last_name'] as String,
        phone: json['phone'] as String,
        addressLine1: json['address_line_1'] as String,
        addressLine2: json['address_line_2'] as String?,
        city: json['city'] as String,
        state: json['state'] as String?,
        postalCode: json['postal_code'] as String?,
        country: json['country'] as String,
        isDefault: json['is_default'] as bool? ?? false,
        fullName: json['full_name'] as String?,
      );

  Map<String, dynamic> toShippingJson() => {
        'first_name': firstName,
        'last_name': lastName,
        'phone': phone,
        'address_line_1': addressLine1,
        if (addressLine2 != null) 'address_line_2': addressLine2,
        'city': city,
        if (state != null) 'state': state,
        if (postalCode != null) 'postal_code': postalCode,
        'country': country,
      };

  Map<String, dynamic> toCreateJson() => {
        if (label != null) 'label': label,
        'first_name': firstName,
        'last_name': lastName,
        'phone': phone,
        'address_line_1': addressLine1,
        if (addressLine2 != null) 'address_line_2': addressLine2,
        'city': city,
        if (state != null) 'state': state,
        if (postalCode != null) 'postal_code': postalCode,
        'country': country,
        'is_default': isDefault,
      };

  final int id;
  final String? label;
  final String firstName;
  final String lastName;
  final String phone;
  final String addressLine1;
  final String? addressLine2;
  final String city;
  final String? state;
  final String? postalCode;
  final String country;
  final bool isDefault;
  final String? fullName;

  String get displayName => fullName ?? '$firstName $lastName';
}
