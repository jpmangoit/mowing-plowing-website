<!DOCTYPE html>
<html lang="en">

<head>
    <title></title>
    <style>
        body {
            background-image: url("https://mowingandplowing.com/assets/home_page_images/grass-with-road.png");
            /* Additional background properties can be set here */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .letter-container {
            max-width: 390px;
            min-height: 400px;
            background: white;
            padding: 17px;
            border: 1px solid #f1f1f1;

            .image-wrapper {
                position: relative;
                cursor: pointer;

                &.modal-open {
                    z-index: 3;

                    &:after {
                        content: "";
                    }
                }

                &:after {
                    content: url(${EditPenIcon});
                    display: block;
                    width: 26px;
                    height: 26px;
                    position: absolute;
                    right: 5px;
                    top: 5px;
                }

                img {
                    max-width: 360px;
                    height: 225px;
                    object-fit: cover;
                    object-position: center;
                    width: 100%;
                }
            }

            >div {
                position: relative;
            }
        }

        .modelControler {
            max-width: auto;
            min-height: auto;
            margin: 10px auto;
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: center;

            @media (max-width: 767px) {
                padding: 0;
                margin: 0;
                width: 100%;
            }
        }

        .imageWrapper {
            width: 100%;
            background-size: cover;
            background-position: center;
            height: 230px;
        }

        .titlecss {
            min-height: 87px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 33px;
            text-align: center;
            margin: -45px auto 0;
            z-index: 2;
            position: relative;
            max-width: 325px;

            h2 {
                white-space: pre-line;
                font-size: 20px;
                font-weight: bold;
                /* color: ${({ color }) => color}; */
            }

            h3 {
                white-space: pre-line;
                font-size: 19px;
                font-weight: bold;
                /* color: ${({ color }) => color}; */
            }
        }

        .letterTextContainer {
            max-width: 325px;
            margin: 0 auto;
            padding: 30px 10px;
            text-align: left;

            p {
                font-size: 15px;
                color: #888;
            }
        }

        .buttonTextLine {
            min-width: 175px;
            background: #01e3fa;
            padding: 1px;
            width: 100%;
            max-width: 325px;
            transition: background 0.2s;
            border: 0;
            margin-bottom: 10px;
            margin-left: 12px;
        }

        .paragraphText {
            align-items: center;
            justify-content: center;
            /*margin-top: -15px;*/
            max-width: 325px;

            p {
                /*white-space: pre-line;*/
                font-size: 17px;
                color: #888;
                text-align: center;
                line-height: 6px !important;
            }
        }

        .paragraphText p {
            line-height: 10px;
        }

        .controllerr {
            max-width: auto;
            min-height: auto;
            margin: 10px auto;
            width: 90%;
            background-image: url("https://mowingandplowing.com/assets/home_page_images/grass-with-road.png");
            background-size: contain;
            background-position: bottom;
            background-repeat: no-repeat;
            padding: 20px;
            display: flex;
            justify-content: center;
            margin: 0;

            @media (max-width: 767px) {
                padding: 0;
                width: 100%;
            }


            img {
                max-width: 360px;
                height: 225px;
                object-fit: cover;
                object-position: center;
                width: 100%;
            }

            div {
                position: relative;
            }
        }
    </style>
</head>

<body>
    <div data-role="module-unsubscribe" class="module" role="module" data-type="unsubscribe" style="
        color: #444444;
        font-size: 12px;
        line-height: 20px;
        padding: 16px 16px 16px 16px;
        text-align: Center;
      " data-muid="4e838cf3-9892-4a6d-94d6-170e474d21e5">
        <div class="controllerr">
            <div class="modelControler">
                <div class="letter-container">
                    <div class="image-wrapper">
                        <div class="imageWrapper">
                            <img class="imageWrapper" src="https://mowingandplowing.com/assets/images/Group-6396.png" alt="Image" />
                        </div>
                        <div>
                            <div class="letterTextContainer">
                                <p>Hello {{ $name }}</p>
                                <p>We have updated your Job's schedule date from {{$date}} to {{$newDate}}. So your job will be scheduled at the new scheduled date. </p>
                                <p>If you have any concern related to this fell free to send email to mowingandplowingcle@gmail.com</p>
                            </div>
                        </div>
                        <div class="buttonTextLine"></div>

                        <div class="image-wrapper" style="color: #13287f">
                            <h3 style="font-size: 15px !important;">Thank you for using Mowing and Plowing!</h3>
                        </div>
                        <div class="paragraphText" style="margin-bottom: 10px">
                            <p>&copy; 2023 Your Company. All rights reserved.</p>
                            <p><br /><a href="https://www.mowingandplowing.com">www.mowingandplowing.com</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>