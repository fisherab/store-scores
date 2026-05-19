<?php

/**
 * This represents a Long King Swissish event.
 */
class Store_Scores_Long_King_Swiss_Type extends Store_Scores_Competition_Type {

    /**
     * Need to return the set of people you have to play
     */
    public function get_opponents($comp_id, $player_id) {
        $competitors = get_post_meta($comp_id,'competitors', true);
        if ($player_id == 0) {
            return  $competitors;
        }
    
        // Create gameslist with all games with $player_id
        $title = explode("games:", get_the_title($comp_id));
        $oppolist = [];
        if ($title[1]) {
            $list = preg_split("/\s+/",$title[1]);
            while ($v1 = array_pop($list)) {
                $v1 = $competitors[$v1];
                $v2 = $competitors[array_pop($list)];
                if (in_array($player_id, [$v1,$v2])) {
                    if ($v1 == $player_id) {
                        $oppolist[] = $v2;
                    } else {
                        $oppolist[] = $v1;
                    }
                }
            }      
        } else {
            $oppolist = [];
        }
        // dumpToFile(['oppolist',$oppolist]);   
      
        // Go through the gameslist and only keep those unplayed
        $played = [];
        $competition = get_post_meta($comp_id);
    	if (array_key_exists('result', $competition)) {
    	    $results = $competition['result'];
            foreach ($results as $result) {
                $result = unserialize($result);
                $you_id =  $result['you']['person'];
                $opp_id =  $result['opp']['person'];
           	    // dumpToFile(['results',$result,$you_id,$opp_id]);   
         	    if ($player_id == $you_id) $played[] = $opp_id;
                if ($player_id == $opp_id) $played[] = $you_id;   
            }            
        } 
        // dumpToFile(['diff',array_diff($oppolist, $played)]);    
	    return  array_diff($oppolist, $played);
    }

    /**
     * Get unique short name to display to admin
     */
    public function get_tag() {
        return "Long King Swiss";
    }

    /** 
     * Get human description of the format
     */
    public function get_description() {
        $html = '<div>';
        $html .= '<p>All your games are predefined in a way that should give all competitors roughly the same strength opposing them.</p>';
        $html .= '<p>You need to win a number of games to enter the knockout stage.</p>';
        $html .= '<p>Note that you do not play everybody, but that everybody has the same number of opponents</p>';
        $html .= '<p>You can play your preselected opponents in any order you wish. After you enter the score your opponents will disappear from your list of people to play.</p>';
        $html .= '</div>';
        return $html;
    }
  
   /**
     *  Return formatted results
     *
     *  $comp_id id of the competition custom post
     */
    public function get_results($comp_id) {
    	$competitors = get_post_meta($comp_id,'competitors', true);
    	foreach ($competitors as $num) {
           $first = get_user_meta($num,'first_name',true);
           $last = get_user_meta($num,'last_name', true);
           $name = $first . ' ' . $last;
    	   $totals[$num] = [$name,$num, 0]; 
    	}
    	$competition = get_post_meta($comp_id);
    	if (array_key_exists('result', $competition)) {
    	    $html = "<h4>Results so far</h4>"; 
    	    $html .= "<div><table>";
            $results = $competition['result'];
            foreach ($results as $result) {
               	$result = unserialize($result);          
                $winner = Store_Scores_Competition_Type::getWinner($result)[1];
                $totals[$winner][2] += 1;
            } 
            usort ($totals, function ($a, $b) {
                if ($a[2] == $b[2]) {
                    return 0;
                }
                return ($a[2] > $b[2]) ? -1 : 1;}); 
            foreach ($totals as $t) {
                $html .= "<tr>";
                $html .= '<td>'.$t[0].'</td>';
                $html .= '<td>'.$t[2].'</td>';
                $html .= "</tr>";
            }
            $html .= '</table></div>';
        }
        if (isset($html)) {
            return $html;
        }
    }
}
