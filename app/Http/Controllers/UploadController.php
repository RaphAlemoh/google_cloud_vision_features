<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Vision\V1\AnnotateFileResponse;
use Google\Cloud\Vision\V1\AsyncAnnotateFileRequest;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\GcsDestination;
use Google\Cloud\Vision\V1\GcsSource;
use Google\Cloud\Vision\V1\InputConfig;
use Google\Cloud\Vision\V1\OutputConfig;

class UploadController extends Controller
{
    public function upload()
    {
        return view('uploads.create');
    }

    public function SafeSearchDetection(Request $request)
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

    public function detectTextInImage(Request $request)
    {

        $request->validate([
            'avatar' => 'required|image|max:10240',
        ]);

        try {

            $imageAnnotatorClient = new ImageAnnotatorClient([
                //we can also keep the details of the google cloud json file in an env and read it as an object here
                'credentials' => config_path('laravel-cloud-features.json')
            ]);

            # annotate the image
            $image = file_get_contents($request->file("avatar"));

            //run the textdection feature on the image
            $response = $imageAnnotatorClient->textDetection($image);


            if ($error = $response->getError()) {
                // returns error from annotator client
                return redirect()->back()
                    ->with('danger', $error->getMessage());
            }

            $texts = $response->getTextAnnotations();

            //to ascertain the number of texts on the image
            $number_of_texts = count($texts);

            //text on image saved in to this variable
            $image_text_content = '';

            foreach ($texts as $text) {

                $image_text_content .= $text->getDescription() . PHP_EOL;

                //the text description on the image
                // print($text->getDescription() . PHP_EOL);

                # get bounds using the vertex feature
                $vertices = $text->getBoundingPoly()->getVertices();
                $bounds = [];
                foreach ($vertices as $vertex) {
                    $bounds[] = sprintf('(%d,%d)', $vertex->getX(), $vertex->getY());
                }

                // to access the bounds of the image
                // print('Bounds: ' . join(', ', $bounds) . PHP_EOL);
            }

            // return [$image_text_content];

            $formatted_text = new HtmlString($image_text_content);

            $imageAnnotatorClient->close();


            //return home with a success message
            return redirect()->route('home')
                ->with('success', "Text detection successful!!! Number of Texts $number_of_texts and text on image uploaded: $formatted_text");
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    public function documentTextDetection(Request $request)
    {

        $request->validate([
            'avatar' => 'required|image|max:10240',
        ]);

        try {
            $docAnnotatorClient = new ImageAnnotatorClient([
                //we can also keep the details of the google cloud json file in an env and read it as an object here
                'credentials' => config_path('laravel-cloud-features.json')
            ]);


            # annotate the image
            $doc = file_get_contents($request->file("avatar"));

            $response = $docAnnotatorClient->documentTextDetection($doc);

            $annotation = $response->getFullTextAnnotation();


            //formatted text
            $formatted_text = new HtmlString($annotation->getText());


            //final unformatted text
            $block_text = '';

            //bounds
            $bounds = [];

            # print out detailed and structured information about document text
            if ($annotation) {
                foreach ($annotation->getPages() as $page) {
                    foreach ($page->getBlocks() as $block) {
                        foreach ($block->getParagraphs() as $paragraph) {
                            foreach ($paragraph->getWords() as $word) {
                                foreach ($word->getSymbols() as $symbol) {
                                    $block_text .= $symbol->getText();
                                }
                                $block_text .= ' ';
                            }
                            $block_text .= "\n";
                        }
                        // printf('Block content: %s', $block_text);
                        // printf(
                        //     'Block confidence: %f' . PHP_EOL,
                        //     $block->getConfidence()
                        // );

                        # get bounds
                        $vertices = $block->getBoundingBox()->getVertices();
                        foreach ($vertices as $vertex) {
                            $bounds[] = sprintf(
                                '(%d,%d)',
                                $vertex->getX(),
                                $vertex->getY()
                            );
                        }
                        // print('Bounds: ' . join(', ', $bounds) . PHP_EOL);
                        // print $block_text;
                    }
                }
                $text_bounds = join(', ', $bounds);
                return redirect()->route('home')
                    ->with('success', "Text detection successful!!! Formatted Text on image uploaded: $formatted_text, Bounds: $text_bounds");
            } else {

                //if no text is found in the document
                print('No text found' . PHP_EOL);
                return redirect()->route('home')
                    ->with('danger', "No text found!!!");
            }

            //return home with a success message
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $docAnnotatorClient->close();
    }



    public function detectPDFinGCS()
    {
        $path = 'gs://path/to/your/document.pdf';
        $output = 'gs://path/to/store/results/';

        # select ocr feature
        $feature = (new Feature())
            ->setType(Type::DOCUMENT_TEXT_DETECTION);

        # set $path (file to OCR) as source
        $gcsSource = (new GcsSource())
            ->setUri($path);
        # supported mime_types are: 'application/pdf' and 'image/tiff'
        $mimeType = 'application/pdf';
        $inputConfig = (new InputConfig())
            ->setGcsSource($gcsSource)
            ->setMimeType($mimeType);

        # set $output as destination
        $gcsDestination = (new GcsDestination())
            ->setUri($output);
        # how many pages should be grouped into each json output file.
        $batchSize = 2;
        $outputConfig = (new OutputConfig())
            ->setGcsDestination($gcsDestination)
            ->setBatchSize($batchSize);

        # prepare request using configs set above
        $request = (new AsyncAnnotateFileRequest())
            ->setFeatures([$feature])
            ->setInputConfig($inputConfig)
            ->setOutputConfig($outputConfig);
        $requests = [$request];

        # make request
        $imageAnnotator = new ImageAnnotatorClient([
            //we can also keep the details of the google cloud json file in an env and read it as an object here
            'credentials' => config_path('laravel-cloud-features.json')
        ]);
        $operation = $imageAnnotator->asyncBatchAnnotateFiles($requests);
        print('Waiting for operation to finish.' . PHP_EOL);
        $operation->pollUntilComplete();

        # once the request has completed and the output has been
        # written to GCS, we can list all the output files.
        preg_match('/^gs:\/\/([a-zA-Z0-9\._\-]+)\/?(\S+)?$/', $output, $match);
        $bucketName = $match[1];
        $prefix = isset($match[2]) ? $match[2] : '';


        $googleConfigFile = file_get_contents(config_path('laravel-cloud-features.json'));

        $storage = new StorageClient([
            'keyFile' => json_decode($googleConfigFile, true)
        ]);
        $bucket = $storage->bucket($bucketName);
        $options = ['prefix' => $prefix];
        $objects = $bucket->objects($options);

        # save first object for sample below
        $objects->next();
        $firstObject = $objects->current();

        # list objects with the given prefix.
        print('Output files:' . PHP_EOL);
        foreach ($objects as $object) {
            print($object->name() . PHP_EOL);
        }

        # process the first output file from GCS.
        # since we specified batch_size=2, the first response contains
        # the first two pages of the input file.
        $jsonString = $firstObject->downloadAsString();
        $firstBatch = new AnnotateFileResponse();
        $firstBatch->mergeFromJsonString($jsonString);

        # get annotation and print text
        foreach ($firstBatch->getResponses() as $response) {
            $annotation = $response->getFullTextAnnotation();
            print($annotation->getText());
        }

        $imageAnnotator->close();
    }
}
