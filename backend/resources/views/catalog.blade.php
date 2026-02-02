<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Catálogo de Produtos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            margin: 0;
            size: A4 portrait;
        }
        
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10pt;
            padding: 0;
            margin: 0;
            background-image: url('{{ storage_path('app/public/gradiente.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .page-header {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.95);
            margin-bottom: 10px;
            position: relative;
            z-index: 10;
        }
        
        .logo {
        width: 175px;
        margin-bottom: 10px;
        }
        
        h1 {
            text-align: center;
            color: #04abeb;
            font-size: 20px;
            margin: 0;
        }
        
        .content {
            text-align: center;
            padding: 20px 15px;
            position: relative;
            z-index: 10;
        }
        
        .catalog-date {
            position: absolute;
            top: 25px;
            left: 20px;
            font-size: 11px;
            font-weight: bold;
            color: #000000;
            text-align: left;
            z-index: 20;
        }
        
        .products-wrapper {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            margin: 20px auto;
            max-width: 95%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
        }
        
        td {
            width: 25%;
            vertical-align: top;
        }
        
        .product-card {
            background: transparent;
            border-radius: 0;
            padding: 5px;
            text-align: left;
            height: 190px;
            box-shadow: none;
        }
        
        .product-image {
            width: 100%;
            height: 110px;
            margin-bottom: 8px;
        }
        
        .no-image {
            width: 100%;
            height: 110px;
            background: #f0f0f0;
            margin-bottom: 8px;
            line-height: 110px;
            text-align: center;
            color: #999;
            font-size: 9px;
            border-radius: 6px;
        }
        
        .product-name {
            font-size: 9px;
            color: #000000;
            height: 28px;
            overflow: hidden;
        }
        
        .product-sku {
            font-size: 9px;
            color: #000000;
            margin-top: 2px;
        }
        
        .product-price {
            margin-top: 2px;
            font-size: 8px;
            color: #000000;
        }
        
        .product-stock {
            font-size: 8px;
            color: #84bc74;
            margin-top: 4px;
            font-weight: bold;
        }
        
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 10px;
            font-size: 15px;
            color: #000000;
            font-weight: bold;
            z-index: 10;
        }
        
        .no-products {
            text-align: center;
            padding: 50px;
            color: #646463;
            font-size: 14px;
            background: white;
            border-radius: 12px;
            margin: 20px;
        }
    </style>
</head>
<body>
    <div class="content">
        @if($products->count() > 0)
            <div class="catalog-date">CATÁLOGO {{ date('d/m/Y') }}</div>
        @endif
        
        <img src="{{ storage_path('app/public/logo.png') }}" alt="Logo" class="logo">
        
        @if($products->count() > 0)
            @php
                $productsArray = $products->all();
                $productsPerPage = 16; // 4 linhas x 4 colunas
                $pages = array_chunk($productsArray, $productsPerPage);
            @endphp
            
            @foreach($pages as $pageIndex => $pageProducts)
                @if($pageIndex > 0)
                    <div style="page-break-before: always;"></div>
                    <img src="{{ storage_path('app/public/logo.png') }}" alt="Logo" class="logo" style="margin-top: 20px;">
                @endif
                
                <div class="products-wrapper">
                    <table>
                        @php
                            // Preencher com espaços vazios para completar a grade
                            $currentCount = count($pageProducts);
                            $emptySlots = $productsPerPage - $currentCount;
                            for ($i = 0; $i < $emptySlots; $i++) {
                                $pageProducts[] = null;
                            }
                            $rows = array_chunk($pageProducts, 4);
                        @endphp
                        
                        @foreach($rows as $row)
                            <tr>
                                @foreach($row as $product)
                                    <td>
                                        @if($product)
                                            <div class="product-card">
                                                @if($product->local_image_path && file_exists($product->local_image_path))
                                                    <img src="{{ $product->local_image_path }}" alt="{{ $product->name }}" class="product-image">
                                                @else
                                                    <div class="no-image">Sem imagem</div>
                                                @endif
                                                
                                                <div class="product-name">ITEM: {{ $product->name }}</div>
                                                <div class="product-sku">SKU: {{ $product->sku }}</div>
                                                <div class="product-price">PREÇO ATACADO: R$ {{ number_format($product->price_atacado, 2, ',', '.') }}</div>
                                                <div class="product-price">PREÇO VENDA SUGERIDO: R$ {{ number_format($product->price_varejo, 2, ',', '.') }}</div>

                                                @if($showStock)
                                                    <div class="product-stock">Estoque: {{ $product->stock }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="product-card"></div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        @else
            <div class="no-products">
                Nenhum produto encontrado com os filtros selecionados.
            </div>
        @endif
    </div>
    
    <div class="page-footer">
        LOJA.GEEKDISTRIBUIDORA.COM.BR
    </div>
</body>
</html>
