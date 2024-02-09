<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Str;


class SearchController extends Controller
{
    public function search(Request $request)
    {
        // \Log::info($request);
        // $barcodes = $request->input('query');
        // $barcodeString = $barcodes[0];
        // $barcodeArray = explode(", ", $barcodeString);
        $barcodes = $request->input('query');
        $barcodeString = $barcodes[0];

        // Split the string by spaces
        $barcodeArray = explode(" ", $barcodeString);

        $results = [];

        foreach ($barcodeArray as $query) {
            // Check if the product already exists in the database
            $product = Product::where('barcode', $query)->first();

            if ($product) {
                // If the product already exists, add it to the existingResults array
                $results[] = $product;
            } else {
                // Check if the query is an ISBN
                if (Str::startsWith($query, '978')) {
                    $isbn_results = $this->getISBNdata($query);
                    $results[] = $isbn_results;
                }
                else {
                    // 1. https://www.upcitemdb.com/api/explorer#!/lookup/get_trial_lookup
                    $upcitemdb_results = $this->getupcitemdb($query);
                    $results[] = $upcitemdb_results;

                    if ($upcitemdb_results == null) {
                        // 2. https://rapidapi.com/relaxed/api/ean-lookup/
                        $barcodelookup_results = $this->getBarcodelookup($query);
                        $results[] = $barcodelookup_results;
                    }
                }
            }
        }

        foreach ($results as &$result) {
            if (is_object($result)) {
                $result->makeHidden(['id', 'created_at', 'updated_at']);
            }
        }

        return $results;
    }

    /**
     * Check if a string is a valid ISBN.
     *
     * @param string $query
     * @return bool
     */
    private function getISBNdata($query)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://devapi.pointnxt.com/api/v1/mcm/getIsbnproduct',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('isbn' => $query),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        // Decode the JSON string into an array
        $responseArray = json_decode($response, true);

        // Check if decoding was successful
        if (json_last_error() == JSON_ERROR_NONE && is_array($responseArray)) {
            $newProduct = new Product();

            $dimensionProduct = isset($responseArray['dimension_length']) && isset($responseArray['dimension_breadth']) && isset($responseArray['dimension_height'])
            ? $responseArray['dimension_length'] . ' * ' . $responseArray['dimension_breadth'] . ' * ' . $responseArray['dimension_height']
            : null;
            

            $newProduct->barcode = $responseArray['code'] ?? null;
            $newProduct->sku = $responseArray['sku'] ?? null;
            $newProduct->title = $responseArray['name'] ?? null;
            $newProduct->description = $responseArray['description'] ?? null;
            $newProduct->images = $responseArray['image'] ?? null;
            $newProduct->manufacturer = $responseArray['manufacturer'] ?? null;
            $newProduct->ingredients = $responseArray['ingredients'] ?? null;
            // $newProduct->brand = $responseArray['brand'] ?? null;
            $newProduct->model = $responseArray['model'] ?? null;
            $newProduct->weight = $responseArray['weight'] ?? null;
            $newProduct->dimension = $dimensionProduct;
            $newProduct->category = $responseArray['config'] ?? [];
            $newProduct->price = $responseArray['price'] ?? 0.00;

            try {
                $newProduct->save();
                \Log::info($newProduct);
            } catch (\Exception $e) {
                \Log::error('Error saving product: ' . $e->getMessage());
            }

            return $newProduct;
        } else {
            \Log::error('Error decoding JSON response: ' . json_last_error_msg());
            return null;
        }
    }
   
    public function getupcitemdb($query) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.upcitemdb.com/prod/trial/lookup?upc='.$query,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $responseArray = json_decode($response, true);

        curl_close($curl);
        if ($responseArray !== null && isset($responseArray['code']) && $responseArray['code'] == 'OK') {
            $total_response = $responseArray['total'];
        
            if ($total_response != 0 && isset($responseArray['items'][0])) {
                $item = $responseArray['items'][0];
                $newProduct = new Product();

                $newProduct->barcode = $query;
                $newProduct->title = $item['title'];
                $newProduct->description = $item['description'];
                $newProduct->brand = $item['brand'];
                $newProduct->category = $item['category'];
                $newProduct->images = json_encode($item['images']);
                $newProduct->weight = $item['weight'];
                $newProduct->model = $item['model'];
                $newProduct->dimension = $item['dimension'];
                $newProduct->lowest_recorded_price = $item['lowest_recorded_price'];
                $newProduct->highest_recorded_price = $item['highest_recorded_price'];

                try {
                    $newProduct->save();
                } catch (\Exception $e) {
                    \Log::error('Error saving product: ' . $e->getMessage());
                }
                return $newProduct;
            }
         
        }

        return null;
    }

    public function getBarcodelookup($query) {
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://barcodes1.p.rapidapi.com/?query=".$query,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "X-RapidAPI-Host: barcodes1.p.rapidapi.com",
                "X-RapidAPI-Key: 354e66361cmsh34e8ec1aa67c85ap15dea7jsnbaaca4488171"
            ],
        ]);
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
    
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $decodedResults = json_decode($response, true);
    
            // Check if 'product' key is present in the decoded response
            if (isset($decodedResults['product'])) {
                $productInfo = $decodedResults['product'];
    
                // Create a new instance of the Product model
                $newProduct = new Product();
    
                $newProduct->barcode = $query;
                $newProduct->upc = $productInfo['attributes']['upc'] ?? null;
                $newProduct->asin = $productInfo['asin'] ?? null;
                $newProduct->title = $productInfo['title'] ?? null;
                $newProduct->description = $productInfo['description'] ?? null;
                $newProduct->images = json_encode($productInfo['images'] ?? null);
                $newProduct->manufacturer = $productInfo['manufacturer'] ?? null;
                $newProduct->ingredients = $productInfo['ingredients'] ?? null;
                $newProduct->brand = $productInfo['brand'] ?? null;
                $newProduct->model = $productInfo['model'] ?? null;
                $newProduct->weight = $productInfo['attributes']['weight'] ?? null; 
                $newProduct->dimension = $productInfo['attributes']['dimension'] ?? null;
                $newProduct->category = implode(', ', $productInfo['category'] ?? []);
                $newProduct->price = $productInfo['attributes']['price'] ?? 0.00;

                // Save the new product
                $newProduct->save();

                return $newProduct;
            }
        }
    }


}
