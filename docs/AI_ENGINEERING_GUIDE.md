**✅ تمام، خلّصنا.**  

إليك **النسخة النهائية V2.0** بعد تطبيق كل التعديلات + التشديد القوي على **Simplicity**.

```md
# AI ENGINEERING GUIDE - Celebrity Connect Platform
**Version 2.0**

## Project Philosophy

هذا المشروع مصمم ليعيش **5-10 سنين** أو أكثر.

**كل قرار** يجب أن يراعي:

- **Simplicity** (الأولوية المطلقة)
- Scalability
- Readability
- Maintainability
- Extensibility
- Low Coupling + High Cohesion
- Testability

**Simple code > Clever code**

---

## Simplicity First (أهم قاعدة في المشروع)

**Always choose the simplest solution** that satisfies current and reasonably expected future requirements.

- تجنب التعقيد غير الضروري.
- تجنب Over-Engineering.
- تجنب تطبيق Design Patterns إلا لو كان فيه قيمة واضحة.
- لو فيه طريقتين، اختار الأبسط.
- Simple code أسهل في الصيانة والتعديل والحذف.

**Simplicity is not optional — it is mandatory.**

---

## Architecture Philosophy

**Business Logic يجب ألا يعتمد أبداً على أي Provider خارجي.**

كل خدمة خارجية (Stream, Agora, Cloudinary, Paymob, Stripe...) يجب أن تكون معزولة خلف **Interface + Adapter**.

الـ Business Layer يتعامل فقط مع الـ Abstraction، مش مع الـ Implementation.

---

## Golden Rules

- Never write code that works only "today".
- Always design for future requirements.
- Prefer explicit over implicit.
- Favor composition over inheritance.
- Fail fast and loudly.
- If a class does more than one thing → split it.

---

## Folder Structure (Modules-First)

```
app/
├── Modules/
│   ├── Auth/
│   ├── User/
│   ├── Creator/
│   ├── Post/
│   ├── Story/
│   ├── Wallet/
│   ├── Payment/
│   ├── Subscription/
│   ├── Chat/
│   ├── Call/
│   ├── Notification/
│   └── Admin/
├── Support/           # DTOs, Traits, Helpers
├── Providers/
└── Infrastructure/    # Adapters only (optional)
```

**Modules** هي الطريقة الأساسية للتنظيم.

---

## Laravel Rules

- Controllers **Thin جداً**: Validate → Authorize → Service → Resource.
- Business Logic → Services.
- Data Access → Repositories (فقط لو الـ Logic معقد).
- Never access external providers directly from Controllers or Services.
- Use Form Requests + API Resources + Policies.

---

## Service & DTO Rules

- كل Feature لها Service خاص بها.
- Never pass `Request` objects to Services → استخدم **DTO**.
- Services تتواصل مع بعضها عبر Interfaces عند الحاجة.

---

## Naming Conventions

- استخدم أسماء واضحة ووصفية.
- تجنب الاختصارات.
- تجنب الأسماء العامة.

**Good**: `CreatorSubscription`, `WalletTransaction`, `MediaAsset`

**Bad**: `Sub`, `Tx`, `Data`, `Obj`

---

## Code Quality Rules

- **One Responsibility**: كل Class لها مسؤولية واحدة واضحة.
- **Method Size**: حاول إبقاء الـ Methods أقل من 40 سطر قدر الإمكان. لو زادت → extract private methods.
- **Configuration over Hardcoding**: أي قيمة ممكن تتغير (Commission, Limits, Fees...) اجعلها configurable.
- **Database**: لا تحفظ Derived Data إلا لو فيه سبب أداء واضح. الـ DB يخزن Facts.

---

## Provider / Adapter Rules

كل Provider خارجي يجب أن يكون له:
- Interface
- Concrete Adapter
- Binding في Service Provider

**لا يُسمح** باستخدام SDK مباشرة خارج الـ Adapter.

---

## Money & Wallet Rules (Critical)

- Never update balance directly.
- Always use `available_balance` + `held_balance`.
- كل عملية مالية **يجب** أن تكون داخل DB Transaction.
- كل حركة Coins يجب أن تكون traceable.

---

## Performance & Scalability

- Paginate by default.
- Avoid N+1 queries (eager loading).
- Queue heavy jobs.
- Cache intelligently.
- Design every module to be independently replaceable.

---

## Security & Error Handling

- JWT + Refresh Tokens
- Proper validation & authorization on every endpoint
- Use Exceptions meaningfully
- Never silently ignore failures

---

## Logging

Log **only** what provides operational value:
- Errors
- Payments & Refunds
- Withdrawals
- External provider failures
- Important business events

تجنب الـ Noisy logs.

---

## AI Coding Workflow (مهم جداً)

لكل Feature جديدة، اتبع الترتيب ده:

1. Understand the requirement clearly.
2. Identify affected modules.
3. Design any needed DB changes.
4. Design the API endpoints.
5. Design DTOs.
6. Design Services.
7. Design Policies & Resources.
8. Implement.
9. Refactor for simplicity and cleanliness.
10. Review the code against **this entire guide**.

---

## AI Decision Making Rules (اقرأها قبل كتابة أي كود)

قبل كتابة أي سطر، اسأل نفسك:

1. Is this the **simplest** solution?
2. Does this add unnecessary complexity?
3. Can this module be replaced independently?
4. Am I coupling business logic to any external provider?
5. Will another developer understand this easily in 6+ months?
6. Can this feature be extended without modifying existing code?
7. Does this follow Laravel best practices and the project's architecture?
8. Is there over-engineering?
9. If we rewrite this in 5 years, would this decision still make sense?
10. **Does this respect Simplicity First?**

إذا الإجابة "No" على أي سؤال → أعد التصميم قبل الكتابة.

---

## Final Statement

**هذا الملف هو Single Source of Truth** لكل القرارات المعمارية في المشروع.

عند أي تعارض بين الكود وهذا الدليل → **هذا الدليل له الأولوية** إلا لو تم تحديثه صراحة.

---
