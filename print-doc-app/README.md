# PrintDoc

## Run locally with XAMPP

1. Start Apache and MySQL, and create the MySQL database `oopp` (or set `PRINT_DOC_DB_NAME`).
2. Set `PRINT_DOC_DB_HOST`, `PRINT_DOC_DB_USER`, and `PRINT_DOC_DB_PASSWORD` if the defaults in `config/database.php` do not match your local database.
3. Open `http://localhost/projen-uxui0/print-doc-app/` in this repository's current XAMPP layout. The app creates its prefixed tables and default pricing on first request.
4. Register an account. Uploads are limited to 15 MB per file and the supported formats are PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, TXT, JPG, and PNG.

The app uses PDF.js from cdnjs to count PDF pages in the browser. Files in other formats can be uploaded, but their page counts must be entered manually. A network connection is needed for automatic PDF page counting.

## Grant admin access

Register the intended admin account, then run this command from PowerShell or Command Prompt:

```text
C:\xampp\php\php.exe C:\xampp\htdocs\projen-uxui0\print-doc-app\admin\promote_admin.php admin@example.com
```

The command is CLI-only. Admins can update page, paper, binding, shipping, bulk discount, and minimum-order prices from **จัดการราคา**.

## Pricing defaults

- Black-and-white: 2 THB/page; color: 5 THB/page.
- From 50 total printed pages: black-and-white rate becomes 0.80 THB/page.
- Paper surcharges per page over A4 70 gsm: A3 +3 THB, B5 +0 THB, A4 80 gsm +0.25 THB, cardstock +3 THB.
- Binding per copy: corner staple 0 THB, spiral 20 THB, coil 30 THB.
- Shipping: 40 THB/order; minimum order: 20 THB.

Prices are recalculated server-side when an order is submitted. The app records the order and estimate; payment processing and fulfillment are not included.

Run the pricing regression checks with:

```text
C:\xampp\php\php.exe C:\xampp\htdocs\projen-uxui0\print-doc-app\tests\pricing_test.php
```

Uploaded files are stored outside direct HTTP access and downloaded through an owner-checked endpoint. Configure PHP's `upload_max_filesize` and `post_max_size` to at least 16 MB to support the displayed per-file limit.
