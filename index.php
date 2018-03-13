<?php
 $db = new PDO('sqlite:data.sqlite');
 $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

 $current_location = "343 Campus Rd, Ithaca, NY 14853";

 $do_search = FALSE;
 $search_fields = array();
 $messages = array();
 $messages_insert = array();
 $image = NULL;
 $sort_by = NULL;


 function exec_sql_query($db, $sql, $params) {
   $query = $db->prepare($sql);
   if ($query and $query->execute($params)) {
     return $query;
   }
   return NULL;
 }

 function distance_comp($a,$b)
 {
   return $a["distance"] - $b["distance"];
 }


$rating_arr = array(
  "0.0" => "i-stars--regular-0",
  "0.5" => "i-stars--regular-0-half",
  "1.0" => "i-stars--regular-1",
  "1.5" => "i-stars--regular-1-half",
  "2.0" => "i-stars--regular-2",
  "2.5" => "i-stars--regular-2-half",
  "3.0" => "i-stars--regular-3",
  "3.5" => "i-stars--regular-3-half",
  "4.0" => "i-stars--regular-4",
  "4.5" => "i-stars--regular-4-half",
  "5.0" => "i-stars--regular-5"
);

function calculate_distance($destination1, $destination2) {
  global $current_location;
  $from = $current_location;
  $to = $destination1.", ".$destination2;
  $from = urlencode($from);
  $to = urlencode($to);
  $data = file_get_contents("http://maps.googleapis.com/maps/api/distancematrix/json?origins=$from&destinations=$to&language=en-EN&sensor=false");
  $data = json_decode($data);
  $time = 0;
  $distance = 0;
  $time_specific = 0;
  $distance_specific = 0;
  foreach($data->rows[0]->elements as $road) {
    $time_specific += $road->duration->value;
    $time      = $road->duration->text;
    $distance  = $road->distance->text;
    $distance_specific += $road->distance->value;
  }
  return array(
    "time" => $time,
    "distance" => $distance,
    "meters" => $distance_specific,
    "seconds" => $time_specific
  );
}

function print_restaurants() {

  global $restaurants;
  global $rating_arr;
  global $sort_by;
  // add time and distance to the array
  $newrestaurants = array();
  foreach ($restaurants as $restaurant) {
    $time_distance = calculate_distance($restaurant["location1"], $restaurant["location2"]);
    $newrestaurants[] = array_merge($restaurant, array(
      "time" => $time_distance["seconds"],
      "distance" => $time_distance["meters"],
      "time_text" => $time_distance["time"],
      "distance_text" => $time_distance["distance"]
    ));
  }

  $restaurants = $newrestaurants;

  if ($sort_by == "distance") {
    uasort($restaurants, 'distance_comp');
  }

  foreach ($restaurants as $restaurant) {

    // esscape the output
    $add = filter_var($restaurant["image_address"], FILTER_SANITIZE_STRING);
    $nam = filter_var($restaurant["name"], FILTER_SANITIZE_STRING);
    $tag = filter_var($restaurant["tags"], FILTER_SANITIZE_STRING);
    $rat = filter_var($restaurant["rating"], FILTER_SANITIZE_STRING);
    $pri = filter_var($restaurant["price"], FILTER_SANITIZE_STRING);
    $loc1 = filter_var($restaurant["location1"], FILTER_SANITIZE_STRING);
    $loc2 = filter_var($restaurant["location2"], FILTER_SANITIZE_STRING);
    $pho = filter_var($restaurant["phone"], FILTER_SANITIZE_STRING);
    $dis = filter_var($restaurant["distance_text"], FILTER_SANITIZE_STRING);
    $tim = filter_var($restaurant["time_text"], FILTER_SANITIZE_STRING);


    // var_dump($restaurant);
    echo "<div class = 'restaurant_info'>";
    echo "<div class = 'logo'>";
    echo "<img src='/images/".htmlspecialchars($add).".jpg' alt = '". htmlspecialchars($nam) ."'>";
    echo "</div><div class = 'info'><div class = 'name'><a class = 'name'>";

    echo htmlspecialchars($nam);
    echo "</a></div><div class = 'i-stars ";

    if ($rat == NULL) {
      echo htmlspecialchars($rating_arr['0.0']);
    }
    else {
      echo htmlspecialchars($rating_arr[$rat]);
    }
    echo " rating-large'></div><div class = 'extra'><a class = 'price'>";
    if ($pri != NULL) {
      echo "Around ".htmlspecialchars($pri)."$ ・";
    }
    echo "</a><a class = 'tags'>";
    echo htmlspecialchars($tag);
    echo "</a></div></div><div class = 'contact'><ul>";
    echo "<li>".htmlspecialchars($loc1)."</li>";
    echo "<li>".htmlspecialchars($loc2)."</li>";
    echo "<li>".htmlspecialchars($pho)."</li>";
    echo "</ul><div class = 'time_distance'>";
    echo "<ul><li>".htmlspecialchars($dis)." from here</li>";
    echo "<li>drive for ".htmlspecialchars($tim)."</li></ul>";
    echo "</div></div></div>";
  }
}


if (isset($_GET['pricelow']) or isset($_GET['pricehigh']) or isset($_GET['tags']) or isset($_GET['name'])) {
  $do_search = TRUE;
  // TODO: filter input for 'search' and 'category'

  $price_low = filter_input(INPUT_GET, 'pricelow', FILTER_VALIDATE_FLOAT);
  if ($_GET['pricelow'] != NULL and $price_low == NULL) {
    array_push($messages, "Invalid input for minimum price.");
  }
  else if ($price_low != NULL) {
    $search_fields += array("pricelow" => $price_low);
  }

  $price_high = filter_input(INPUT_GET, 'pricehigh', FILTER_VALIDATE_FLOAT);
  if ($_GET['pricehigh'] != NULL and $price_high == NULL) {
    array_push($messages, "Invalid input for maximum price.");
  }
  else if ($price_high != NULL) {
    $search_fields += array("pricehigh" => $price_high);
  }

  if ($price_low != NULL and $price_high != NULL and $price_high < $price_low) {
    array_push($messages, "Invalid input that max < min.");
    unset($search_fields["pricelow"]);
    unset($search_fields["pricehigh"]);
  }

  $tags = filter_input(INPUT_GET, 'tags', FILTER_SANITIZE_STRING);
  $tags = trim($tags," ");
  if ($tags != NULL and $tags != "") {
    $search_fields += array("tags" => $tags);
  }

  $name = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_STRING);
  $name = trim($name," ");
  if ($name != NULL and $name != "") {
    $search_fields += array("name" => $name);
  }

} else {
  // No search provided, so set the product to query to NULL
  $do_search = FALSE;
}

if (isset($_GET['sort'])) {
  $sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
}



if (isset($_POST["submit_insert"])) {
  $name_insert = filter_input(INPUT_POST, 'nameinsert', FILTER_SANITIZE_STRING);
  $tags_insert = filter_input(INPUT_POST, 'tagsinsert', FILTER_SANITIZE_STRING);
  $rating_insert = filter_input(INPUT_POST, 'ratinginsert', FILTER_VALIDATE_FLOAT);
  $price_insert = filter_input(INPUT_POST, 'priceinsert', FILTER_VALIDATE_INT);
  $location1_insert = filter_input(INPUT_POST, 'location1insert', FILTER_SANITIZE_STRING);
  $location2_insert = filter_input(INPUT_POST, 'location2insert', FILTER_SANITIZE_STRING);
  $phone_insert = filter_input(INPUT_POST, 'phoneinsert', FILTER_SANITIZE_STRING);


  $invalid_review = False;

  if ( $rating_insert < 0 or $rating_insert > 5 ) {
    $invalid_review = TRUE;
    array_push($messages_insert, "Invalid rating.");
  }
  if ( $price_insert < 0 ) {
    $invalid_review = TRUE;
    array_push($messages_insert, "Invalid price.");
  }

  if (!preg_match("/^\([0-9]{3}\) [0-9]{3}-[0-9]{4}$/", $phone_insert)) {
    $invalid_review = TRUE;
    array_push($messages_insert, "Invalid phone number.");
  }

  if ($invalid_review) {
    array_push($messages_insert, "Failed to add restaurant!");
  } else {
    // TODO: write SQL to insert review

    $sql = "INSERT INTO restaurants (name, tags, location1, location2, phone, image_address, rating, price) VALUES (:nam, :tag, :loc1, :loc2, :pho, :img, :rat, :pri)";

    $name_insert = filter_var($name_insert, FILTER_SANITIZE_STRING);
    $tags_insert = filter_var($tags_insert, FILTER_SANITIZE_STRING);
    $rating_insert = filter_var($rating_insert, FILTER_VALIDATE_FLOAT);
    $price_insert = filter_var($price_insert, FILTER_VALIDATE_INT);
    $location1_insert = filter_var($location1_insert, FILTER_SANITIZE_STRING);
    $location2_insert = filter_var($location2_insert, FILTER_SANITIZE_STRING);
    $phone_insert = filter_var($phone_insert, FILTER_SANITIZE_STRING);

    if ($image == NULL) {
      $image = "default";
    }

    $params = array(':nam' => $name_insert, ':tag' => $tags_insert, ':loc1' => $location1_insert, ':loc2' => $location2_insert, ':pho' => $phone_insert, ':img' => $image, ':rat' => $rating_insert, ':pri' => $price_insert);

    $result = exec_sql_query($db, $sql, $params);
    if ($result) {
      array_push($messages_insert, "Your restaurant has been record. Thank you!");
    } else {
      array_push($messages_insert, "Failed to add restaurant, try again!");
    }
  }
} else {
  $test = "outpost";
}

?>


<!DOCTYPE html>
<html>


<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" type="text/css" href="styles/all.css" media="all" />
  <!-- <link href='http://fonts.googleapis.com/css?family=Oleo+Script' rel='stylesheet' type='text/css'> -->

  <title>Home</title>
</head>

<body id="index">
  <?php
  include("includes/header.php");
  ?>

  <div id="content">
    <div class = "super_container">

        <div class = "main">
          <div class = "top-shelf-grey">
            <h2>
              fine dining near
              <a class = "filter-title"><?php echo htmlspecialchars($current_location); ?></a>
            </h2>
            <form id="searchForm" action="index.php" method="get">
              <div class = "filter">
                <label>name </label>
                <input class = "name" type="text" name="name"/>
              </div>

              <div class = "filter">
                <label>tags </label>
                <input class = "tags" type="text" name="tags"/>
              </div>

              <div class = "filter">
                <label>price </label>
                <input class = "price" type="text" name="pricelow"/>
                $ -
                <input class = "price" type="text" name="pricehigh"/>
                $
              </div>

              <div class = "filter">
                <label>sort by </label>
                <select class="sort" name="sort">
                  <option value="" selected disabled>none</option>
                  <option value="distance">distance</option>
                  <option value="rating">rating</option>
                  <option value="price">price</option>
                </select>
              </div>

              <button class = "filter" type="submit">Filter</button>

            </form>

            <?php
            // Write out any messages to the user.
            foreach ($messages as $message) {
              echo "<p><strong>" . htmlspecialchars($message) . "</strong></p>\n";
            }
            ?>

          </div>

          <?php
          if ($do_search) {
            if (array_key_exists("pricelow", $search_fields)) {
              $min = $search_fields["pricelow"];
            }
            else {
              $min = -1;
            }
            if (array_key_exists("pricehigh", $search_fields)) {
              $max = $search_fields["pricehigh"];
            }
            else {
              $max = INF;
            }

            $sql = "SELECT * FROM restaurants WHERE (price <= :max) AND (price >= :min)";
            $params = array(':max' => $max , ':min' => $min);

            if (array_key_exists("tags", $search_fields)) {
              $sql = $sql."AND (tags LIKE '%' || :tags || '%')";
              $params += array(':tags' => $search_fields["tags"]);
            }
            if (array_key_exists("name", $search_fields)) {
              $sql = $sql."AND (name LIKE '%' || :name || '%')";
              $params += array(':name' => $search_fields["name"]);
            }

          } else  {
            $sql = "SELECT * FROM restaurants";
            $params = array();
          }

          if ($sort_by == "price") {
            $sql = $sql. "ORDER BY price ASC";
          }
          if ($sort_by == "rating") {
            $sql = $sql. "ORDER BY rating DESC";
          }

          $restaurants = exec_sql_query($db, $sql, $params)->fetchAll();

          // sort the results

          print_restaurants();
          ?>
        </div>

        <!-- <div class = "search">

        </div> -->

        <div class = "side">
          <h2>New restaurant?</h2>
          <form id="insertForm" action="index.php" method="post">

            <div class = "insert">
              <label>name<a class = "red"> *</a> </label>
              <input class = "name-insert" type="text" name="nameinsert" required/>

            </div>

            <div class = "insert">
              <label>tags<a class = "red"> *</a> </label>
              <input class = "tags-insert" type="text" name="tagsinsert" required/>
            </div>

            <div class = "short-insert">
              <label>price </label>
              <input class = "price" type="text" name="priceinsert"/>
              $
            </div>

            <div class = "short-insert">
              <label>rating </label>
              <select class = "rating" name="ratinginsert">
                <option selected disabled>select</option>
                <option value="0">0</option>
                <option value="0.5">0.5</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
                <option value="3.5">3.5</option>
                <option value="4">4</option>
                <option value="4.5">4.5</option>
                <option value="5">5</option>
              </select>
              <!-- <input class = "price" type="text" name="ratinginsert"/> -->
            </div>



            <div class = "insert">
              <label>location<a class = "red"> *</a> </label>
              <input class = "location-insert" type="text" name="location1insert" required/>
            </div>

            <div class = "insert">
              <label>location (city)<a class = "red"> *</a> </label>
              <input class = "location-insert" type="text" name="location2insert" required/>
            </div>

            <div class = "insert">
              <label>phone number<a class = "red"> *</a> </label>
              <input class = "location-insert" type="text" name="phoneinsert" required/>
              <label>(xxx) xxx-xxxx </label>
            </div>

            <?php
            foreach ($messages_insert as $message) {
              echo "<p><strong>" . htmlspecialchars($message) . "</strong></p>\n";
            }
            ?>
            <input class = "insert" name = "submit_insert" type="submit" value="Join in!">
          </form>
        </div>
    </div>
  </div>
  <?php include("includes/footer.php");?>
</body>
</html>
