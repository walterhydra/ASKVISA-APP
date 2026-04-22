class ApplicationQuestion {
  final int id;
  final String label;
  final String fieldKey;
  final String fieldType;
  final String validationRules;
  final List<QuestionOption> options;

  ApplicationQuestion({
    required this.id,
    required this.label,
    required this.fieldKey,
    required this.fieldType,
    required this.validationRules,
    required this.options,
  });

  factory ApplicationQuestion.fromJson(Map<String, dynamic> json) {
    var list = json['options'] as List? ?? [];
    List<QuestionOption> optionsList = list.map((i) => QuestionOption.fromJson(i)).toList();

    return ApplicationQuestion(
      id: int.parse(json['id'].toString()),
      label: json['label'] ?? '',
      fieldKey: json['field_key'] ?? '',
      fieldType: json['field_type'] ?? 'text',
      validationRules: json['validation_rules'] ?? '{}',
      options: optionsList,
    );
  }
}

class QuestionOption {
  final String value;
  final String label;

  QuestionOption({required this.value, required this.label});

  factory QuestionOption.fromJson(Map<String, dynamic> json) {
    return QuestionOption(
      value: json['option_value'] ?? '',
      label: json['option_label'] ?? '',
    );
  }
}
