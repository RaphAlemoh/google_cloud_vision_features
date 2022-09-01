<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

class UploadController extends Controller
{
    public function upload()
    {
        return view('uploads.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:10240',
        ]);

        try {

            // we can make the code neater by using a global helper function or a trait

            $imageAnnotatorClient = new ImageAnnotatorClient([
                //we can also keep the details of the google cloud json file in an env and read it as an object here
                'credentials' => config_path('laravel-cloud-features.json')
            ]);

            $image_path = $request->file("avatar");
            //get the content of the image
            $imageContent = file_get_contents($image_path);

            //run the safe search detection on the image
            $response = $imageAnnotatorClient->safeSearchDetection($imageContent);

            if ($error = $response->getError()) {
                // returns error from annotator client
                return redirect()->back()
                ->with('danger', $error->getMessage());
            }

            //get the annotation of the response
            $safe = $response->getSafeSearchAnnotation();

            $likelihood_status =  0;

            //the values in the array of the response are indexed from 0-5

            $likelihood_status = ($safe->getAdult() >= 3) ? 1 : 0;
            $likelihood_status = ($safe->getSpoof() >= 3) ? 1 : 0;
            $likelihood_status = ($safe->getViolence() >= 3) ? 1 : 0;
            $likelihood_status = ($safe->getRacy() >= 3) ? 1 : 0;

            if ($likelihood_status === 1) {
                //the image has some unwanted content there in
                return redirect()->back()
                ->with('danger', 'This image is not allowed on this platform!!!');
            }

            //close the annotation client
            $imageAnnotatorClient->close();

            //return home with a success message
            return redirect()->route('home')
                ->with('success', 'Uploaded successfully!!!');

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
