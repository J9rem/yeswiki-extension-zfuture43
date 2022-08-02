<?php

/*
 * This file is part of the YesWiki Extension zfuture43.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace YesWiki\Zfuture43\Controller;

use Throwable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use YesWiki\Core\ApiResponse;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\YesWikiController;

class ApiController extends YesWikiController
{

    /**
     * @Route("/api/triples", methods={"GET"}, options={"acl":{"public", "+"}},priority=2)
     */
    public function ByResource()
    {
        extract($this->extractTriplesParams(INPUT_GET, "not empty"));
        if (!empty($apiResponse)) {
            return $apiResponse;
        }
        $value = empty($username) ? null : "%\\\"user\\\":\\\"{$username}\\\"%";
        $triples = $this->getService(TripleStore::class)->getMatching(
            null,
            $property,
            $value,
            "=",
            "=",
            "LIKE"
        );
        return new ApiResponse(
            $triples,
            Response::HTTP_OK
        );
    }
    
    /**
     * @Route("/api/triples/{resource}", methods={"GET"}, options={"acl":{"public", "+"}},priority=2)
     */
    public function getTriplesByResource($resource)
    {
        extract($this->extractTriplesParams(INPUT_GET, $resource));
        if (!empty($apiResponse)) {
            return $apiResponse;
        }
        $value = empty($username) ? null : "%\\\"user\\\":\\\"{$username}\\\"%";
        $triples = $this->getService(TripleStore::class)->getMatching(
            $resource,
            $property,
            $value,
            "=",
            "=",
            "LIKE"
        );
        return new ApiResponse(
            $triples,
            Response::HTTP_OK
        );
    }

    /**
     * @Route("/api/triples/{resource}", methods={"POST"}, options={"acl":{"public", "+"}},priority=2)
     */
    public function setTriple($resource)
    {
        extract($this->extractTriplesParams(INPUT_POST, $resource));
        if (!empty($apiResponse)) {
            return $apiResponse;
        }
        if (empty($property)) {
            return new ApiResponse(
                ['error' => 'Property should not be empty !'],
                Response::HTTP_BAD_REQUEST
            );
        }
        if (empty($username)) {
            $username = $this->getService(UserManager::class)->getLoggedUser()['name'];
        }
        $rawValue = $_POST['value'] ?? [];
        if (is_array($rawValue)) {
            $rawValue = array_filter($rawValue, function ($elem) {
                return is_scalar($elem);
            });
        } elseif (is_scalar($value)) {
            $rawValue = [
                'value' => $value
            ];
        } else {
            $rawValue = [];
        }
        $rawValue['user'] = $username;
        $rawValue['date'] = date('Y-m-d H:i:s');
        $value = json_encode($rawValue);
        $result = $this->getService(TripleStore::class)->create(
            $resource,
            $property,
            $value,
            "",
            ""
        );
        return new ApiResponse(
            ['result' => $result],
            in_array($result, [0,3]) ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
    
    /**
     * @Route("/api/triples/{resource}/delete", methods={"POST"}, options={"acl":{"public", "+"}},priority=2)
     */
    public function deleteTriples($resource)
    {
        extract($this->extractTriplesParams(INPUT_POST, $resource));
        if (!empty($apiResponse)) {
            return $apiResponse;
        }
        
        if (empty($property)) {
            return new ApiResponse(
                ['error' => 'Property should not be empty !'],
                Response::HTTP_BAD_REQUEST
            );
        }
        $rawFilters = $_POST['filters'] ?? [];
        if (is_array($rawFilters)) {
            $rawFilters = array_filter($rawFilters, function ($elem) {
                return is_scalar($elem);
            });
        } else {
            $rawFilters = [];
        }
        if (!empty($username)) {
            $rawFilters['user'] = $username;
        }

        $triples = null;
        if (!empty($rawFilters)) {
            foreach ($rawFilters as $key => $rawValue) {
                $value = empty($rawValue) ? null : "%\\\"{$key}\\\":\\\"{$rawValue}\\\"%";
                $newTriples = $this->getService(TripleStore::class)->getMatching(
                    $resource,
                    $property,
                    $value,
                    "=",
                    "=",
                    "LIKE"
                );
                if (empty($newTriples)) {
                    $triples = [];
                } elseif (is_null($triples)) {
                    $triples = $newTriples;
                } elseif (!empty($triples)) {
                    $newIds = array_map(function ($elem) {
                        $elem['id'];
                    });
                    $triples = array_filter($triples, function ($elem) use ($newIds) {
                        return in_array($elem['id'], $newIds);
                    });
                }
            }

            foreach ($triples as $triple) {
                $this->getService(TripleStore::class)->delete(
                    $triple['resource'],
                    $triple['property'],
                    $triple['value'],
                    "",
                    ""
                );
            }
        }

        return new ApiResponse(
            $triples,
            Response::HTTP_OK
        );
    }

    private function extractTriplesParams(string $method, $resource): array
    {
        $property = null;
        $username = null;
        $apiResponse = null;
        if (empty($resource)) {
            $apiResponse = new ApiResponse(
                ['error' => 'Resource should not be empty !'],
                Response::HTTP_BAD_REQUEST
            );
        } else {
            $property = filter_input($method, 'property', FILTER_UNSAFE_RAW);
            $property = ($property === false) ? "" : htmlspecialchars(strip_tags($property));
            if (empty($property)) {
                $property = null;
            }
            $username = filter_input($method, 'username', FILTER_UNSAFE_RAW);
            $username = ($username === false) ? "" : htmlspecialchars(strip_tags($username));
            if (empty($username)) {
                if (!$this->wiki->UserIsAdmin()) {
                    $username = $this->getService(UserManager::class)->getLoggedUser()['name'];
                } else {
                    $username = null;
                }
            }
            $currentUser = $this->getService(UserManager::class)->getLoggedUser();
            if (!$this->wiki->UserIsAdmin() && $currentUser['name'] != $username) {
                $apiResponse = new ApiResponse(
                    ['error' => 'Not authorized to access a triple of another user if not admin !'],
                    Response::HTTP_UNAUTHORIZED
                );
            }
        }
        return compact(['property','username','apiResponse']);
    }
}
