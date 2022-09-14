<?php


class TodoistAPI
{

    private $api_key;
    private $project_id = -1;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    private function request($url, bool $post_request = false)
    {

        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($post_request) {
                curl_setopt($ch, CURLOPT_POST, true);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " . $this->api_key
            ));

            $response = curl_exec($ch);

            if ($response === false && curl_errno($ch)) {
                print_r(curl_error($ch));
                die();
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code != intval(200)) {
                print("Ressource introuvable : " . $http_code);
            }
        } catch (\Throwable $th) {
            throw $th;
        } finally {
            curl_close($ch);
        }
        return json_decode($response, true);
    }

    public function get_projets()
    {
        return $this->request("https://api.todoist.com/rest/v1/projects");
    }

    public function get_active_tasks()
    {
        $all_tasks = $this->request("https://api.todoist.com/rest/v2/tasks");
        $res = [];
        foreach ($all_tasks as $task) {
            if ($this->project_id < 0 || $task['project_id'] == $this->project_id) {
                $res[] = $task;
            }
        }
        uasort($res, function ($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return ($a['order'] < $b['order']) ? -1 : 1;
        });

        return $res;
    }

    /**
     * Add filter to use only task of the project
     * @param string $name the name of the project
     * @return bool return true if the project exist false otherwise
     */
    public function fiter_project(string $name): bool
    {
        $projets = $this->get_projets();
        foreach ($projets as $proj) {
            if ($proj['name'] == $name) {
                $this->project_id = $proj['id'];
                return true;
            }
        }
        $this->project_id = -1;
        return false;
    }

    /**
     * @throws Throwable
     */
    public function makeTaskDone($task_id)
    {
        $this->request(sprintf("https://api.todoist.com/rest/v1/tasks/%d/close", $task_id), true);
    }
}
