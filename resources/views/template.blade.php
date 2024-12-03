<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .left-column .element {
            height: 10px;
            background-color: #f0f0f0;
            margin-bottom: 10px;
        }
        .select2-container .select2-selection--single {
            height: 38px;
            padding: 5px;
        }
        .disabled-input {
            font-weight: bold; /* Make text bold */
            background-color: #f0f0f0; /* Greyed-out background */
            color: #6c757d; /* Grey text color */
            border: 1px solid #ced4da; /* Keep the border consistent */
        }
    </style>
</head>
<body>
    <!-- Language Switcher -->
    <div class="language-switcher p-3">
        <select id="languageSelect" class="form-select">
            <option value="en">English</option>
            <option value="ar">Arabic</option>
        </select>
    </div>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column -->
            <div class="col-2 left-column p-3">
                @for ($i = 1; $i <= 5; $i++)
                    <div class="element w-100"></div>
                @endfor
            </div>
            
            <!-- Right Column -->
            <div class="col-10 right-column">
                <!-- Form Header -->
                <div class="form-header p-3 bg-light">
                    <h2 class="mb-3" id="formHeader">Form Header</h2>
                    <form>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="name" class="form-label" id="nameLabel">Name:</label>
                                <input type="text" class="form-control" id="name" name="name">
                            </div>
                            <div class="col-md-4">
                                <label for="phone" class="form-label" id="phoneLabel">Phone Number:</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-md-4">
                                <label for="date" class="form-label" id="dateLabel">Date:</label>
                                <input type="date" class="form-control" id="date" name="date">
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Subform -->
                <div class="subform p-3">
                    <h3 class="mb-3" id="subformHeader">Subform</h3>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemTable">
                            <thead>
                                <tr>
                                    <th id="productHeader">Product</th>
                                    <th id="quantityHeader">Quantity</th>
                                    <th id="priceHeader">Price</th>
                                    <th id="subtotalHeader">Subtotal</th>
                                    <th id="actionHeader">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" id="addRow">Add Row</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-end" id="subtotalLabel">Subtotal:</td>
                                    <td><input type="number" class="form-control" id="orderSubtotal" readonly></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end" id="shippingLabel">Shipping Charge:</td>
                                    <td><input type="number" class="form-control" id="shippingCharge" value="0"></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end" id="discountLabel">Discount:</td>
                                    <td><input type="number" class="form-control" id="discount" value="0"></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong id="orderTotalLabel">Order Total:</strong></td>
                                    <td><input type="number" class="form-control disabled-input" id="orderTotal" readonly disabled></td> <!-- Added class for styling -->
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center p-3">
        <button id="generatePDF" class="btn btn-success">Generate PDF</button>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- jsPDF-AutoTable -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.10/jspdf.plugin.autotable.min.js"></script>
    
    <script>
    $(document).ready(function() {
        const { jsPDF } = window.jspdf;

        // Sample products
        const products = [
            {id: 1, name: 'Product 1', price: 10.99},
            {id: 2, name: 'Product 2', price: 15.99},
            {id: 3, name: 'Product 3', price: 20.99},
            {id: 4, name: 'Product 4', price: 25.99},
            {id: 5, name: 'Product 5', price: 30.99},
            {id: 6, name: 'Product 6', price: 35.99},
            {id: 7, name: 'Product 7', price: 40.99},
            {id: 8, name: 'Product 8', price: 45.99},
            {id: 9, name: 'Product 9', price: 50.99},
            {id: 10, name: 'Product 10', price: 55.99},
        ];


        // Counter for unique IDs
        let rowCounter = 0;

        // Function to add a new row
        function addNewRow() {
            rowCounter++;
            const newRow = `
                <tr id="row${rowCounter}">
                    <td>
                        <select class="form-control product-select" name="product[]" data-row="${rowCounter}">
                            <option value="">Select a product</option>
                            ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name}</option>`).join('')}
                        </select>
                    </td>
                    <td><input type="number" class="form-control quantity" name="quantity[]" value="1" min="1" data-row="${rowCounter}"></td>
                    <td><input type="number" class="form-control price" name="price[]" readonly data-row="${rowCounter}"></td>
                    <td><input type="number" class="form-control subtotal" name="subtotal[]" readonly data-row="${rowCounter}"></td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm removeRow" data-row="row${rowCounter}">
                            Remove
                        </button>
                    </td>
                </tr>
            `;
            $('#itemTable tbody').append(newRow);
            initializeSelect2();
            calculateOrderTotal(); // Add this line to update totals when a new row is added
        }

        // Initialize Select2 for product selection
        function initializeSelect2() {
            $('.product-select').select2({
                placeholder: 'Select a product',
                allowClear: true
            });
        }

        // Function to calculate order total
        function calculateOrderTotal() {
            let subtotal = 0;
            $('.subtotal').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });
            
            const shippingCharge = parseFloat($('#shippingCharge').val()) || 0;
            const maxDiscount = subtotal + shippingCharge;
            
            // Update max attribute of discount input
            $('#discount').attr('max', maxDiscount.toFixed(2));
            
            let discount = parseFloat($('#discount').val()) || 0;
            discount = Math.min(discount, maxDiscount); // Ensure discount doesn't exceed max
            $('#discount').val(discount.toFixed(2));
            
            $('#orderSubtotal').val(subtotal.toFixed(2));
            const total = Math.max(subtotal + shippingCharge - discount, 0); // Ensure total is not negative
            $('#orderTotal').val(total.toFixed(2));
        }

        // Modify calculateSubtotal function
        function calculateSubtotal(row) {
            const quantity = parseFloat($(`input.quantity[data-row="${row}"]`).val()) || 0;
            const price = parseFloat($(`input.price[data-row="${row}"]`).val()) || 0;
            const subtotal = quantity * price;
            $(`input.subtotal[data-row="${row}"]`).val(subtotal.toFixed(2));
            calculateOrderTotal();
        }

        // Event listeners for shipping charge and discount
        $('#shippingCharge, #discount').on('input', function() {
            $(this).val(Math.max(parseFloat($(this).val()) || 0, 0)); // Ensure non-negative values
            calculateOrderTotal();
        });

        // Add row button click event
        $('#addRow').click(function() {
            addNewRow();
        });

        // Remove row button click event (using event delegation)
        $('#itemTable').on('click', '.removeRow', function() {
            const rowId = $(this).data('row');
            $(`#${rowId}`).remove();
            calculateOrderTotal(); // Recalculate total after removing a row
        });

        // Product selection change event
        $('#itemTable').on('change', '.product-select', function() {
            const row = $(this).data('row');
            const selectedOption = $(this).find('option:selected');
            const price = selectedOption.data('price');
            $(`input.price[data-row="${row}"]`).val(price);
            calculateSubtotal(row);
        });

        // Quantity change event
        $('#itemTable').on('input', '.quantity', function() {
            const row = $(this).data('row');
            const quantity = parseFloat($(this).val()) || 0;
            $(this).val(Math.max(quantity, 1)); // Ensure quantity is at least 1
            calculateSubtotal(row);
        });

        // Price change event (in case you want to allow manual price adjustments)
        $('#itemTable').on('input', '.price', function() {
            const row = $(this).data('row');
            const price = parseFloat($(this).val()) || 0;
            $(this).val(Math.max(price, 0)); // Ensure non-negative price
            calculateSubtotal(row);
        });

        // Add an initial row
        addNewRow();

        const translations = {
            en: {
                formHeader: "Form Header",
                nameLabel: "Name:",
                phoneLabel: "Phone Number:",
                dateLabel: "Date:",
                subformHeader: "Subform",
                productHeader: "Product",
                quantityHeader: "Quantity",
                priceHeader: "Price",
                subtotalHeader: "Subtotal",
                actionHeader: "Action",
                subtotalLabel: "Subtotal:",
                shippingLabel: "Shipping Charge:",
                discountLabel: "Discount:",
                orderTotalLabel: "Order Total:"
            },
            ar: {
                formHeader: "عنوان النموذج",
                nameLabel: "الاسم:",
                phoneLabel: "رقم الهاتف:",
                dateLabel: "التاريخ:",
                subformHeader: "النموذج الفرعي",
                productHeader: "المنتج",
                quantityHeader: "الكمية",
                priceHeader: "السعر",
                subtotalHeader: "المجموع الفرعي",
                actionHeader: "الإجراء",
                subtotalLabel: "المجموع الفرعي:",
                shippingLabel: "رسوم الشحن:",
                discountLabel: "الخصم:",
                orderTotalLabel: "إجمالي الطلب:"
            }
        };

        // Language switcher functionality
        $('#languageSelect').change(function() {
            const selectedLang = $(this).val();
            $('html').attr('lang', selectedLang).attr('dir', selectedLang === 'ar' ? 'rtl' : 'ltr');

            // Update text content based on selected language
            $('#formHeader').text(translations[selectedLang].formHeader);
            $('#nameLabel').text(translations[selectedLang].nameLabel);
            $('#phoneLabel').text(translations[selectedLang].phoneLabel);
            $('#dateLabel').text(translations[selectedLang].dateLabel);
            $('#subformHeader').text(translations[selectedLang].subformHeader);
            $('#productHeader').text(translations[selectedLang].productHeader);
            $('#quantityHeader').text(translations[selectedLang].quantityHeader);
            $('#priceHeader').text(translations[selectedLang].priceHeader);
            $('#subtotalHeader').text(translations[selectedLang].subtotalHeader);
            $('#actionHeader').text(translations[selectedLang].actionHeader);
            $('#subtotalLabel').text(translations[selectedLang].subtotalLabel);
            $('#shippingLabel').text(translations[selectedLang].shippingLabel);
            $('#discountLabel').text(translations[selectedLang].discountLabel);
            $('#orderTotalLabel').text(translations[selectedLang].orderTotalLabel);
        });

        // Generate PDF functionality
        $('#generatePDF').click(function() {
            const { jsPDF } = window.jspdf; // Ensure jsPDF is correctly referenced
            const doc = new jsPDF();
            doc.text("Form Data", 10, 10);
            doc.text("Name: " + $('#name').val(), 10, 20);
            doc.text("Phone Number: " + $('#phone').val(), 10, 30);
            doc.text("Date: " + $('#date').val(), 10, 40);
            
            // Prepare data for the table
            const orderItems = [];
            $('#itemTable tbody tr').each(function() {
                const product = $(this).find('.product-select option:selected').text();
                const quantity = $(this).find('.quantity').val();
                const price = $(this).find('.price').val();
                const subtotal = $(this).find('.subtotal').val();
                orderItems.push([product, quantity, price, subtotal]);
            });

            // Add order items to PDF as a table
            doc.autoTable({
                head: [['Product', 'Quantity', 'Price', 'Subtotal']],
                body: orderItems,
                startY: 50, // Start below the form data
                theme: 'grid'
            });

            doc.save("form-data.pdf");
        });
    });
    </script>
</body>
</html>