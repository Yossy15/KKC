# PROJECT-WIDE UI/UX STANDARDIZATION

## Objective

ตรวจสอบและปรับปรุง UI/UX ของทั้งโปรเจกต์ให้มีรูปแบบเดียวกันทุกหน้า โดยต้องรักษา functionality, business logic, data flow และ backend behavior เดิมทั้งหมด

เป้าหมายคือให้ทุกหน้าเหมือนเป็นระบบเดียวกัน ไม่ใช่แต่ละหน้ามีสไตล์ของตัวเอง

---

## 1. Audit ทั้งโปรเจกต์ก่อนแก้ไข

ก่อนแก้ไขโค้ด:

1. สำรวจโครงสร้างโปรเจกต์ทั้งหมด
2. ตรวจสอบทุกหน้า HTML/PHP
3. ตรวจสอบ CSS ทั้งหมด
4. ตรวจสอบ JavaScript ที่เกี่ยวข้องกับ UI
5. ตรวจสอบ component/class ที่ถูกใช้ซ้ำ
6. หา duplicated CSS / duplicated UI patterns
7. หา inline style ที่ควรย้ายออก
8. หา layout ที่แต่ละหน้าทำไม่เหมือนกัน
9. ระบุ breakpoint และ responsive behavior ที่มีอยู่
10. ห้ามเริ่มแก้ทีละไฟล์โดยไม่มีภาพรวมของโปรเจกต์

สร้างรายการก่อนแก้ไข:

- Global styles
- Layout
- Navbar
- Sidebar
- Container
- Card
- Button
- Input
- Select
- Table
- Modal
- Form
- Alert/Notification
- Pagination
- Empty state
- Loading state
- Responsive behavior

---

# 2. สร้าง Design System กลาง

กำหนดมาตรฐาน UI กลางสำหรับทั้งโปรเจกต์

หากมี style เดิมอยู่แล้ว ให้ยึด style เดิมเป็นหลักและปรับให้เป็นระบบเดียวกัน

สร้าง/ปรับ Global CSS เช่น:

```css
:root {
    --color-primary: ...;
    --color-secondary: ...;
    --color-background: ...;
    --color-surface: ...;
    --color-text: ...;
    --color-muted: ...;
    --color-border: ...;

    --radius-sm: ...;
    --radius-md: ...;
    --radius-lg: ...;

    --spacing-xs: ...;
    --spacing-sm: ...;
    --spacing-md: ...;
    --spacing-lg: ...;
    --spacing-xl: ...;

    --font-size-sm: ...;
    --font-size-md: ...;
    --font-size-lg: ...;
    --font-size-xl: ...;
}
```

ห้ามกำหนดค่าซ้ำหลายจุดโดยไม่จำเป็น

---

# 3. Typography

ทำให้ typography เหมือนกันทุกหน้า

กำหนดมาตรฐานสำหรับ:

- body
- h1
- h2
- h3
- h4
- paragraph
- label
- input
- button
- table
- navigation
- muted text

ไม่ให้แต่ละหน้าใช้ font-size / font-weight แตกต่างกันโดยไม่มีเหตุผล

---

# 4. Layout

สร้างมาตรฐาน layout กลาง

ทุกหน้าควรมี:

```text
Page
 ├── Header / Navbar
 ├── Main Container
 │    ├── Page Header
 │    └── Page Content
 └── Footer (ถ้ามี)
```

กำหนด:

- max-width
- page padding
- content spacing
- section spacing
- alignment
- vertical rhythm

ให้เหมือนกันทุกหน้า

---

# 5. Responsive Design

ทำ Responsive Design ให้ทุกหน้า

ต้องรองรับอย่างน้อย:

### Desktop
≥ 1200px

### Tablet
768px – 1199px

### Mobile
< 768px

ตรวจสอบทุก component ว่า:

- ไม่ overflow
- ไม่เกิด horizontal scrolling โดยไม่ตั้งใจ
- text ไม่ล้น
- button ไม่ล้น container
- table รองรับหน้าจอเล็ก
- form ปรับ column ได้
- card ปรับจำนวน column ได้
- navigation ปรับรูปแบบบน mobile
- modal ไม่เกิน viewport
- image ไม่ล้น container

ใช้ CSS responsive เป็นหลัก

หลีกเลี่ยงการตรวจขนาดหน้าจอด้วย JavaScript หาก CSS สามารถทำได้

---

# 6. Component Consistency

Component ที่เหมือนกันต้องใช้ style เดียวกันทั้งโปรเจกต์

ตัวอย่าง:

### Button

```text
Primary
Secondary
Danger
Outline
Disabled
```

### Input

```text
Default
Focus
Error
Disabled
```

### Card

ต้องมีมาตรฐานเดียวกันเรื่อง:

- padding
- border
- radius
- shadow
- spacing
- title
- description
- action

### Table

ต้องเหมือนกันเรื่อง:

- header
- row height
- border
- padding
- responsive behavior

---

# 7. PHP / HTML

ห้ามเปลี่ยน business logic

สามารถปรับ:

- HTML structure
- class names
- wrapper
- semantic HTML
- accessibility attributes
- responsive classes

ได้ หากจำเป็นต่อ UI

แต่ต้องรักษา:

- PHP logic
- database queries
- form processing
- authentication
- authorization
- session
- API behavior
- validation
- existing functionality

---

# 8. CSS Cleanup

ค้นหาและแก้:

- duplicated CSS
- unused CSS
- conflicting selectors
- overly specific selectors
- inline styles
- !important ที่ไม่จำเป็น
- inconsistent spacing
- inconsistent colors
- inconsistent border-radius
- inconsistent font sizes

ถ้ามี style เดียวกันหลายหน้า ให้รวมเป็น Global CSS / shared component style

อย่าสร้าง CSS ใหม่ซ้ำเพียงเพื่อแก้ปัญหาเฉพาะหน้า

---

# 9. Naming Convention

ทำ class naming ให้สม่ำเสมอ

เลือก convention เดียว เช่น:

```text
.page
.page-header
.page-content

.card
.card-header
.card-body
.card-footer

.form
.form-group
.form-label
.form-input

.btn
.btn-primary
.btn-secondary
.btn-danger
```

ห้ามสร้างชื่อ class ใหม่ที่มีความหมายซ้ำกับ class เดิมโดยไม่จำเป็น

---

# 10. UI Visual Consistency

ตรวจสอบทุกหน้าให้มีความสม่ำเสมอในเรื่อง:

- สี
- Font
- Font weight
- Font size
- Spacing
- Border
- Border radius
- Shadow
- Button
- Icon
- Alignment
- Width
- Height
- Card
- Form
- Table
- Navigation

หาก component เดียวกันปรากฏหลายหน้า ต้องทำให้หน้าตาเหมือนกัน

---

# 11. Accessibility

ปรับปรุงโดยไม่ทำลาย functionality:

- semantic HTML
- label สำหรับ input
- alt สำหรับ image
- button ใช้ `<button>` เมื่อเหมาะสม
- keyboard focus
- visible focus state
- contrast
- aria-label เมื่อจำเป็น

---

# 12. ห้ามทำ

ห้าม:

- เปลี่ยน business logic
- เปลี่ยน database schema
- เปลี่ยน API contract
- ลบ functionality ที่ใช้งานอยู่
- เปลี่ยน URL routing โดยไม่จำเป็น
- เปลี่ยนชื่อ PHP variables โดยไม่จำเป็น
- เปลี่ยนชื่อ database fields
- เปลี่ยน authentication flow
- เพิ่ม dependency ใหม่โดยไม่จำเป็น
- redesign แบบสุ่มโดยไม่มีมาตรฐานกลาง
- แก้เฉพาะหน้าเดียวแล้วหยุด

---

# 13. วิธีการทำงาน

ทำงานตามลำดับ:

### Phase 1 — Audit

สำรวจทั้งโปรเจกต์และระบุปัญหา

### Phase 2 — Design System

สร้างมาตรฐาน:

- Colors
- Typography
- Spacing
- Radius
- Shadows
- Container
- Breakpoints

### Phase 3 — Shared Components

ปรับ component ที่ใช้ร่วมกันก่อน:

- Navbar
- Sidebar
- Button
- Form
- Card
- Table
- Modal
- Alert

### Phase 4 — Page Layout

ปรับทุกหน้าให้ใช้ layout เดียวกัน

### Phase 5 — Responsive

ตรวจ Desktop → Tablet → Mobile

### Phase 6 — Cleanup

ลบ duplicated / conflicting CSS และ code ที่ไม่จำเป็น

### Phase 7 — Final Audit

ตรวจทั้งโปรเจกต์อีกครั้ง

---

# 14. Validation

หลังแก้ไขทุกหน้า ต้องตรวจ:

```text
[ ] Desktop
[ ] Tablet
[ ] Mobile
[ ] No horizontal overflow
[ ] Navbar consistent
[ ] Container consistent
[ ] Typography consistent
[ ] Buttons consistent
[ ] Forms consistent
[ ] Cards consistent
[ ] Tables consistent
[ ] Spacing consistent
[ ] Colors consistent
[ ] Responsive behavior consistent
[ ] Existing functionality still works
[ ] No console errors
[ ] No PHP errors
[ ] No broken links
[ ] No duplicated CSS where avoidable
```

---

# 15. Final Requirement

อย่าปรับ UI แบบ "หน้าใครหน้ามัน"

ให้คิดว่าโปรเจกต์นี้เป็น **ระบบเดียวกันทั้งหมด**

หากพบว่า Page A และ Page B มี component ประเภทเดียวกันแต่หน้าตาไม่เหมือนกัน:

> สร้างมาตรฐานกลาง แล้วแก้ทั้งสองหน้าให้ใช้มาตรฐานเดียวกัน

Priority:

```text
1. Existing functionality
2. Consistency
3. Responsive
4. Accessibility
5. Maintainability
6. Visual polish
```

ก่อนจบงาน ต้องสามารถอธิบายได้ว่า:

- Global UI standard อยู่ที่ไหน
- Shared components อยู่ที่ไหน
- Responsive breakpoints คืออะไร
- Component ไหนถูกทำให้ reusable
- มีไฟล์ไหนถูกแก้
- มี duplicated CSS ไหนถูกกำจัด
- มี functionality ไหนที่ได้รับผลกระทบหรือไม่

หากมีความเสี่ยงว่าจะกระทบ functionality ให้หยุดและตรวจสอบก่อนแก้ไข