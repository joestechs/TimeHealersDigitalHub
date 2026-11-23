USE thdh;

INSERT IGNORE INTO chatbot_settings
  (id,is_enabled,bot_name,greeting,fallback_message,support_path,updated_at)
VALUES
  (1,1,'THDH Support','Hello! I can help with our services, digital products, accounts, and how to reach our team.','I do not have a verified answer for that yet. Please contact our team and we will help you.','/contact',NOW());

INSERT IGNORE INTO chatbot_knowledge
  (stable_key,question,answer,category,keywords,link_label,link_path,status,sort_order,source_type,created_at)
VALUES
  ('8a62e8dcff88c6b4e2a9693321cb3cbac26d8d57f9e8d8365e56724767059a9b',
   'What is Time Healers Digital Hub?',
   'Time Healers Digital Hub is a digital services studio and marketplace. We bring practical web, app, marketing, design, video, training, and digital product solutions together to help customers move projects forward.',
   'company','about|company|purpose|digital hub','About THDH','/about','published',10,'seed',NOW()),
  ('195d644c0f7dc9a649d4be3c93b3c6c7a76220327edcdca10851679e520e7bf6',
   'What services do you offer?',
   'Our core services are web development, app development, digital marketing, graphic design, video editing, and corporate training. Visit the Services page to explore them or contact us about a specific goal.',
   'services','services|web development|app development|marketing|graphic design|video editing|training','Explore services','/services','published',20,'seed',NOW()),
  ('6b7ba3fd300d87ca0ebf373c474334305922434a2666508548cf6a7828457be5',
   'How can I request a custom project or quote?',
   'Tell us about your goals, requirements, and timing through the project inquiry form. Our team will review it and follow up about scope and next steps.',
   'services','custom project|quote|quotation|project|customize|consultation','Start a project inquiry','/contact','published',30,'seed',NOW()),
  ('841058853181bdc3f7d6a42e280303e1665b5310a55d8870f7e3821a26ef1b1d',
   'How do digital product purchases work?',
   'Browse the published product catalog for current product details and prices. Products sold through an external vendor use that vendor’s official checkout. Direct THDH purchases are not yet available and require PayPal credentials and webhook configuration.',
   'products','products|digital products|purchase|buy|checkout|price|payment','Browse products','/products','published',40,'seed',NOW()),
  ('d5e454215045fd0b6dbffd790ad60b411d484a9e526d8f11f61c81a192606b7c',
   'Can I pay for an order through THDH?',
   'Some products may use an external vendor’s official checkout. Direct THDH checkout is not currently enabled and requires payment configuration. Please follow the instructions on the product page. The chatbot cannot take payments or verify an order.',
   'products','payment|pay|paypal|order|transaction|checkout','View products','/products','published',50,'seed',NOW()),
  ('9d1f463d29090248e195f8d071df79b572215f8c00e2bd017f8d568739916a39',
   'How do I create an account or sign in?',
   'Use Create account to register, or Sign in if you already have an account. Your account area includes profile settings and any order history associated with your account. For sign-in problems, contact our team and never share your password in chat.',
   'accounts','account|register|registration|sign in|login|profile|password|orders','Sign in or create an account','/login','published',60,'seed',NOW()),
  ('c3b229d9629f5bfa7c548c8aada1b5fdfe424e872a1c64e3a7e86beb113a0392',
   'Where can I read articles and guides?',
   'Published articles and practical digital-work resources are available in the Knowledge Hub.',
   'content','articles|blog|knowledge hub|guides|resources','Open the Knowledge Hub','/blog','published',70,'seed',NOW()),
  ('386c53064eb4dc7559dc57fc7de9f37677ac134b9edca4454a1a483a7fde0ed9',
   'How can I contact customer support?',
   'Send our team a message through the Contact page. Include your project or product question, but never include passwords, full payment-card details, or other sensitive account information.',
   'contact','contact|support|help|customer service|team','Contact our team','/contact','published',80,'seed',NOW()),
  ('aba29d969ed04ca254185d91d9f73b368ac64a9b978c34f8638931b92ef0a854',
   'Can the chatbot access my order or account details?',
   'No. This assistant cannot access or change private account, order, or payment records. Sign in to view your account information, or contact our team for help.',
   'privacy','privacy|personal data|order status|account details|payment details','Open your account','/account','published',90,'seed',NOW());
