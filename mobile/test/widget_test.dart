// Basic smoke test for the Boshpana.ai app.
import 'package:flutter_test/flutter_test.dart';

import 'package:boshpana/main.dart';

void main() {
  testWidgets('App builds and shows the wordmark', (WidgetTester tester) async {
    await tester.pumpWidget(const BoshpanaApp());
    expect(find.text('boshpana.ai'), findsOneWidget);
  });
}
