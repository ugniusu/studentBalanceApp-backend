import express from "express";
import mysql from "mysql";
import cors from "cors";

const app = express();
const port = 8080; //portas i kuri kreipiasi back endas
const db = mysql.createConnection({
  host: "localhost",
  user: "root",
  password: "",
  database: "maitinimas_gile",
});
//if there is a authentication problem
//ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password';

//leidzia is issores siusti json duomenis manau
app.use(express.json());
//specifikuioji domento varda i kuri kreipiasi
app.use(cors());

//kreipiamasi (requiest) i home pae esancia funkcijoa ar i pati home page kad atvaizduotu
app.get("/", (req, res) => {
  //grazinima zinue atvaizdavimui
  res.json("hello this is the back end");
});

app.get("/temployee", (req, res) => {
  //grazinimi duomenys is db
  const q = "SELECT * FROM temployee";
  db.query(q, (err, data) => {
    if (err) return res.json(err);
    return res.json(data);
  });
});
app.post("/get_name_by_card_nr", (req, res) => {
  //grazinimi duomenys is db
  const { CardNo } = req.body;
  const q = "SELECT * FROM temployee where CardNo = " + req.body.CardNo;
  db.query(q, (err, data) => {
    if (err) return res.json(err);
    return res.json(data);
  });
});

//irasymas i db

app.post("/temployee", (req, res) => {
  //grazinimi duomenys is db
  // const q = "INSERT INTO `temployee`(`EmployeeID`, `EmployeeCode`, `EmployeeName`, `name` , `surname` , `EnglishName` , `CardNo` , `balance` , `pin`,"+
  // "`EmpEnable` , `Sex`, `PersonCode` , `Home` , `Phone` , `Email` , `Car` , `JobID` , `DeptID`,`Photo` ,`RegDate` , `RegBy` ,`EndDate` , `ACCESSID` , `Leave`"+
  // "`LeaveDate`, `BeKQ` , `Password` , `MapID` , `XPoint` , `YPoint`, `MapVisible` , `OwnerDoor` , `LastEventID` , `Event2EmpID` ,`TimeStampx`, `ShowCardNo`,"+
  // " `Note1` , `Note2` , `Note3` , `Note4` , `Note5` ,`TimeStamp`, `isBlackCard`, `AcsString` , `ControlerCardId` , `StartDate` , `last_balance_update_neopay`,"+
  // " `last_balance_update_fees`, `max_main_credits` , `max_add_credits` , `gets_free_food` , `max_free_credits` , `order_settings`,`CashAllow` , `last_order_ucode`,"+
  // "  `image` , `Status` , `Deleted` , `action_uid`, `lum` , `last_edited_user` , `last_edit_dt` , `max_day_money_limit` , `return_balance`)"+
  // "VALUES (738, '', '', 'test03', 'test03', '', 'ESF', 143.00, '1234', 1, 0, '2013-09-28 00:00:00', '', '', '', '', '', "+
  // " NULL, 1, '', '2023-06-15 09:50:11', 780, NULL, NULL, 0, NULL, 1, '', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, '', '', '', '0', '0', NULL, 0, NULL,"+
  //  "NULL, NULL, NULL, NULL, 10.00, 10.00, 0, 10.00, 0, 0, NULL, NULL, NULL, 0, 780, 1, 780, '2023-06-15 09:57:08', 10.00, 0.00);"
  //  console.log(q);
  const q = "INSERT INTO temployee (name,surname,CardNo,Birthday) VALUES (?)";
  //const values =["testreact", "testreact","84228611","2013-05-05"]
  const values = [
    req.body.name,
    req.body.surname,
    req.body.CardNo,
    req.body.Birthday,
  ];
  db.query(q, [values], (err, data) => {
    if (err) return res.json(err);
    return res.json("temployee has been created successfully");
  });
});

/**
 * db connectas
 * false - kreipasi i weekly
 * jei skaicius reikiasi i nurodyta mokykla
 *
 */
app.post("/connectSystem", (req, res) => {
  const systemID = req.body.systemID;
  if (systemID) {
    systems = db.query(`select * from systems where id = '${systemID}'`);
    if (systems[0]["status"] != 0) {
      this.conn.close();
      this.conn.connect(
        "localhost",
        systems[0]["db_user"],
        systems[0]["db_key"],
        "maitinimas_" + systems[0]["db_name"]
      );
      const status = this.conn.ping();
      return res.json(status);
    } else {
      this.conn.close();
      this.conn.connect("localhost", "root", "", "maitinimas_db");
      const status = this.conn.ping();
      return res.json(status);
    }
  } else {
    this.conn.close();
    this.conn.connect("localhost", "root", "", "maitinimas_db");
    const status = this.conn.ping();
    return res.json(status);
  }
});

//pasikreipimas i porta
app.listen(port, () => {
  console.log(`Connected to backend, Server is running on port ${port}`);
});
