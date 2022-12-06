<!doctype html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/placeholder-loading.min.css">
<!--    <link rel="stylesheet" href="css/jquery.mobile-1.5.0-rc1.min.css">-->
    <link rel="stylesheet" href="css/main.css">
    <title>Hello, world!</title>
  </head>
  <body>
    <div class="wrapper">
          <nav id="sidebar">
            <div class="sidebar-header">
              <h3>Mail folders</h3>
            </div>
          </nav>
        <div id="content" class="col">
          <div class="container-fluid px-0 px-md-1 px-lg-2">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
              <button class="btn" type="button" id="sidebarCollapse">
                <span class="navbar-toggler-icon"></span>
              </button>
              <span class="navbar-brand mr-auto" href="#">             
                <div class="btn-group" id="menu-main">
                  <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <?php echo iSession::getvar("email"); ?>
                  </button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="#">properties</a>
                    <a class="dropdown-item" id="logoutbutton">logout</a>
                  </div> 
                </div>               
              </span>
            </nav>
            <div class="row">
              <div class="col">
                <div class="container" id="tool-bar">
                  <div class="row justify-content-between align-items-center my-2" id="tool-bar">
                    <div class="col text-truncate" id="foldername_header">Current folder name</div>
                    <div class="col-auto">
                      <button type="button" class="btn btn-secondary">delete</button>
                      <button type="button" class="btn btn-secondary">forward</button>
                      <button type="button" class="btn btn-secondary">move</button>
                      <button type="button" class="btn btn-secondary">new</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
<!--            <div class="col" id="mess_list">-->
              <div class="container" id="mailreader" display="none">
                  <div class="row" id="mailreader-headers">
                    <div class="col">
                      <div class="row">
                        <div class="col mx-0 px-2 msubject text-truncate"></div>
                      </div>
                      <div class="row">
                        <div class="col mx-0 px-2 mfrom text-truncate"></div>
                      </div>
                      <div class="row justify-content-between align-items-center">
                        <div class="col-auto px-2"></div>
                        <div class="col-auto px-0" id="messagebtns">
                          <div class="btn-group dropleft">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              ...
                            </button>
                            <div class="dropdown-menu">
                              <a class="dropdown-item" href="#">properties</a>
                            </div> 
                          </div>               
                          <button type="button" class="btn btn-secondary">reply</button>
                        </div>
                      </div>
                    </div>
                  </div> 
                  <div class="row" id="mailreader-content">
                    <div class="col mx-0 px-1"></div>
                  </div>               
              </div>
              <div class="container" id="messages">
<!--                <div class="row my-2 align-items-center d-flex align-items-stretch">
                  <div class="col-auto list_legend d-flex justify-content-center mx-1 mx-md-2">N</div> 
                  <div class="d-flex col-3 col-md-2 mx-1 mx-md-2 px-1 px-md-2 list_legend align-items-center">from</div>
                  <div class="d-flex col-5 col-md-7 col-xl-8 mx-1 mx-md-2 px-1 px-md-2 list_legend align-items-center">subject</div>
                  <div class="col col-md-1 px-1 d-flex align-items-center list_legend">...</div>
                </div>
               <input type=checkbox class="chooser-chk" id="chooser-0">
                <div class="row my-2 align-items-center d-flex align-items-stretch">
                  <div class="col-auto mess_logo mx-1 mx-md-2"><label class="pillow d-flex align-items-center justify-content-center" for="chooser-0">2</label></div>
                  <div class="d-flex col-3 col-md-2 mx-1 mx-md-2 px-1 px-md-2 mfrom align-items-center"><div class="text-truncate">From field bla bla bla</div></div>
                  <div class="d-flex col-5 col-md-7 col-xl-8 mx-1 mx-md-2 px-1 px-md-2 msubject align-items-center"><div class="text-truncate">Subject field without attach alskdjfhlkajshd lkjashdfkjh alksjdfhlas aksjdhflakjsd alsdkjfhjdfhs kjhdfs</div></div>
                  <div class="col col-md-1 px-1 d-flex align-items-center mdate">13:45</div>
                </div>
                <input type=checkbox class="chooser-chk" id="chooser-1">
                <div class="row my-2 align-items-center d-flex align-items-stretch new">
                  <div class="col-auto mess_logo mx-1 mx-md-2"><label class="pillow d-flex align-items-center justify-content-center" for="chooser-1">3</label></div>
                  <div class="d-flex col-3 col-md-2 mx-1 mx-md-2 px-1 px-md-2 mfrom align-items-center"><div class="text-truncate">From field</div></div>
                  <div class="d-flex col-5 col-md-7 col-xl-8 mx-1 mx-md-2 px-1 px-md-2 msubject align-items-center"><div class="text-truncate">&#128206; Subject field with attach</div></div>
                  <div class="col col-md-1 px-1 d-flex align-items-center mdate">23 июл.</div> 
                </div> -->
              </div>
              <div class="ph-item">
                  <div class="ph-col-12">
                      <div class="ph-row">
                          <div class="ph-col big round"></div>
                          <div class="ph-col big rest"></div>
                          <div class="ph-col-12"></div>
                          <div class="ph-col-6"></div>
                          <div class="ph-col-6 empty"></div>
                      </div>
                  </div>
                  <div class="ph-col-12">
                      <div class="ph-row">
                          <div class="ph-col big round"></div>
                          <div class="ph-col big rest"></div>
                          <div class="ph-col-12"></div>
                          <div class="ph-col-6"></div>
                          <div class="ph-col-6 empty"></div>
                      </div>
                  </div>
                  <div class="ph-col-12">
                      <div class="ph-row">
                          <div class="ph-col big round"></div>
                          <div class="ph-col big rest"></div>
                          <div class="ph-col-12"></div>
                          <div class="ph-col-6"></div>
                          <div class="ph-col-6 empty"></div>
                      </div>
                  </div>                
                  <div class="ph-col-12">
                      <div class="ph-row">
                          <div class="ph-col big round"></div>
                          <div class="ph-col big rest"></div>
                          <div class="ph-col-12"></div>
                          <div class="ph-col-6"></div>
                          <div class="ph-col-6 empty"></div>
                      </div>
                  </div>                                  
              </div>
<!--            </!div> -->
          </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="js/jquery.mobile-1.5.0-rc1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
  </body>
</html>