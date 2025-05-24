## SYMFONY 
## Exploring Symfony for the First Time
#### Requirements:
* Symfony 5.4 (currently not supported as the latest version is 7.2)
* PHP 7.3
* Twig - not reactive (options like Turbo or UX are not available and don't help much)
* DataTables.net (not the best decision in my opinion, due to performance and complexity issues)
* MySQL

#### Task:
* Create a **User** entity (non-standard) with a nullable **role** (wrong requirement in terms of security, to be honest), self-referenced by **manager_id** (nullable).
* Get data from external public(not owned api) websocket given url of crypto rate and update (db and templates...)
* Session auto logout after 10 minutes or so
* Upon login, update the user's login time and set the **manager_id** to null (reason is unclear, but  ok)
* Admin can set or remove any manager for regular users or other managers (but should avoid creating loops, or the database may break — not mentioned, but important).
* A manager can only assign managers to users or other managers in their subtree (without creating loops and while being aware of changes).
* The user has limited permissions and cannot do much
* There was an additional task to add trades for users with live updates of rates and side calculations (decided to skip).
* Admin and rep should see 3 tables (in my case 2 tables - as trades skipped) at once on the same page managing everithing directly from table select(setting manager) which takes us to SPA not exactly Twig thing


#### Problem - Solution - Conclusion

| Problem           | Solution                                                                  | Conclusion                                |
| -----------       | -----------                                                               | ----------                                |
| **Session auto logout**| Back-end and front-end listeners                                         | Works                                     |
| **External websocket**| Node.js WebSocket with token whitelisted IP sending to symfony api route            | Possible, but front-end won't update live  |
| **Internal websocket**| None - mercure won't work with php 7.3 and loop not native thing, xml or jsonp dangerous|  Reactive front and subscribed to websocket(internal) acting on sse needed|
| **User db index**    | Recursive - bad for large data, nested list - highly changeable not stable...| Done but won't work for large data       |
| **Controlled log**    | Monolog service and tail like commands (if not on Linux)                     | Works fine                               |
| **JSON requests exceptions** | Kernel event listener and JSON response when expected + front-end handling | Works fine                               |


#### Final thoughts
* Symfony is a flexible and robust framework.
* Anyone using Symfony should always stay updated with the latest PHP version, bundles, and Symfony itself (this framework evolves rapidly, and support for older versions is not available).
* A reactive front-end should be in a front-end-oriented framework like React, Vue, or any other. This approach is purely SPA-based and isn't ideal for Symfony's traditional architecture.
* There is no real organization where the admin manages not only their direct subordinates but also the very last worker daily, assigning new roles.
* Polling won't work effectively because slight changes in cryptocurrency can result in thousands of dollars in fluctuations.
* The logic behind the user entity is convoluted and overly complicated; it shouldn’t be designed this way.
In my opinion, only direct subordinates should be managed (re-assigned) and not on each login. Only admin may be with manager_id null... Then it is possible to use a nested list strategy or reflection class with a resolver for relations (ugly set to Path — not a solution). Sessions should be managed from the database for better and faster control.

